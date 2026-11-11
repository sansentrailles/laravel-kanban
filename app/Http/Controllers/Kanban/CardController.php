<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateCardAction;
use App\Actions\Kanban\MoveCardAction;
use App\Actions\Kanban\RecordCardActivityAction;
use App\Actions\Kanban\SetCardCompletionAction;
use App\Actions\Kanban\UpdateCardAction;
use App\DTO\Kanban\CreateCardDTO;
use App\DTO\Kanban\TextSnapshotDTO;
use App\DTO\Kanban\UpdateCardDTO;
use App\Enums\Kanban\CardActivityType;
use App\Events\Kanban\CardCreated;
use App\Events\Kanban\CardMoved;
use App\Events\Kanban\CardUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\CardCompletionRequest;
use App\Http\Requests\Kanban\CardCreateRequest;
use App\Http\Requests\Kanban\CardOrderRequest;
use App\Http\Requests\Kanban\CardUpdateRequest;
use App\Http\Resources\Kanban\CardResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function __construct(
        private readonly MoveCardAction $moveCardAction,
        private readonly UpdateCardAction $updateCardAction,
        private readonly CreateCardAction $createCardAction,
        private readonly RecordCardActivityAction $recordCardActivityAction,
        private readonly SetCardCompletionAction $setCardCompletionAction,
    ) {}

    public function store(CardCreateRequest $request, Column $column): JsonResponse
    {
        $card = DB::transaction(function () use ($request, $column) {
            // Блокируем колонку до проверки лимита: без этого два параллельных
            // создания увидят одинаковое число карточек и оба пройдут проверку.
            $lockedColumn = Column::whereKey($column->id)->lockForUpdate()->firstOrFail();

            if ($lockedColumn->isOverLimit()) {
                throw ValidationException::withMessages([
                    'wip_limit' => "WIP-лимит колонки «{$lockedColumn->title}» достигнут ({$lockedColumn->wip_limit}). Карточка не может быть создана.",
                ]);
            }

            $lastCard = $lockedColumn->cards()->reorder('ord', 'desc')->first();

            $card = $this->createCardAction->execute(new CreateCardDTO(
                title: $request->validated('title'),
                columnId: $lockedColumn->id,
            ));

            // Перемещаем в конец, чтобы карточка получила ord после последней.
            return $this->moveCardAction->execute($card, $lockedColumn, $lastCard, null);
        });

        $card = $card->fresh(['labels', 'checklists', 'assignees', 'comments.user']);

        broadcast(new CardCreated($card))->toOthers();

        $actor = $request->user();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: CardActivityType::CardCreated,
            context: [
                'priority' => $card->priority?->value,
            ]
        );

        return response()->json([
            'card' => new CardResource($card),
        ]);
    }

    public function setCompletion(CardCompletionRequest $request, Card $card): JsonResponse
    {
        $completed = $request->boolean('completed');

        $result = DB::transaction(function () use ($card, $completed): array {
            $locked = Card::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCompleted() === $completed) {
                return ['card' => $locked, 'changed' => false];
            }

            return [
                'card' => $this->setCardCompletionAction->execute($locked, $completed),
                'changed' => true,
            ];
        });

        $card = $result['card'];
        $card->load('labels', 'assignees');

        if (! $result['changed']) {
            return response()->json([
                'success' => true,
                'card' => new CardResource($card),
                'message' => 'Статус уже актуален',
            ]);
        }

        broadcast(new CardUpdated($card))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $request->user(),
            type: $completed ? CardActivityType::CardCompleted : CardActivityType::CardReopened,
        );

        return response()->json([
            'success' => true,
            'card' => new CardResource($card),
            'message' => $completed ? 'Карточка завершена' : 'Карточка возвращена в работу',
        ]);
    }

    public function orders(CardOrderRequest $request): JsonResponse
    {
        $card = Card::findOrFail($request->validated('cardId'));
        $column = Column::findOrFail($request->validated('columnId'));
        $prevCard = Card::find($request->validated('prevCardId'));
        $nextCard = Card::find($request->validated('nextCardId'));

        $fromColumnId = $card->column_id;
        $fromOrd = (string) $card->ord;

        $movedCard = $this->moveCardAction->execute($card, $column, $prevCard, $nextCard);

        broadcast(new CardMoved($movedCard))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $movedCard,
            actor: $request->user(),
            type: CardActivityType::CardMoved,
            context: [
                'from_column_id' => $fromColumnId,
                'to_column_id' => $movedCard->column_id,
                'from_ord' => $fromOrd,
                'to_ord' => (string) $movedCard->ord,
                'prev_card_id' => $prevCard?->id,
                'next_card_id' => $nextCard?->id,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Порядок обновлен',
        ]);
    }

    public function update(CardUpdateRequest $request, Card $card)
    {
        $data = $request->validated();
        $dto = UpdateCardDTO::fromRequest($request);

        $before = [
            'title' => $card->title,
            'description' => $card->description,
            'priority' => $card->priority,
            'start_date' => $card->start_date,
            'due_date' => $card->due_date,
        ];

        $labelsBefore = $card->labels()->get(['kanban_labels.id', 'kanban_labels.name', 'kanban_labels.color'])->keyBy('id');
        $assigneesBefore = $card->assignees()->get(['users.id', 'users.name'])->keyBy('id');

        // Обновляем основные поля через Action

        [$updatedCard, $labelsAfter, $assigneeAfter] = DB::transaction(function () use ($card, $dto, $data) {
            $updatedCard = $this->updateCardAction->execute($card, $dto);

            // Синхронизируем метки (если они были переданы и прошли валидацию)
            if (isset($data['label_ids'])) {
                $updatedCard->labels()->sync($data['label_ids']);
            }

            // Синхронизируем исполнителей (по аналогии)
            if (isset($data['assignee_ids'])) {
                $updatedCard->assignees()->sync($data['assignee_ids']);
            }

            return [
                $updatedCard,
                $updatedCard->labels()->get(['kanban_labels.id', 'kanban_labels.name', 'kanban_labels.color'])->keyBy('id'),
                $updatedCard->assignees()->get(['users.id', 'users.name'])->keyBy('id'),
            ];
        });

        $updatedCard->load([
            'labels',
            'assignees',
        ]);

        broadcast(new CardCreated($updatedCard))->toOthers();

        $actor = $request->user();

        $this->recordScalarChanges($updatedCard, $actor, $before);

        // Связи - отдельные события
        $this->recordRelationChanges(
            card: $updatedCard,
            actor: $actor,
            type: CardActivityType::CardLabelsChanged,
            before: $labelsBefore,
            after: $labelsAfter,
            snapshot: static fn ($label): array => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ],
        );

        $this->recordRelationChanges(
            card: $updatedCard,
            actor: $actor,
            type: CardActivityType::CardAssigneesChanged,
            before: $assigneesBefore,
            after: $assigneeAfter,
            snapshot: static fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Обновлено',
            'data' => $updatedCard,
            'labels' => $updatedCard->labels,
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function recordScalarChanges(Card $card, User $actor, array $before): void
    {
        $normalize = [
            'title' => static fn (?string $value): ?string => $value,
            'description' => fn (?string $value): ?array => TextSnapshotDTO::from($value)?->toArray(),
            'priority' => static fn ($value): ?string => $value?->value,
            'start_date' => static fn ($value): ?string => $value?->format('Y-m-d'),
            'due_date' => static fn ($value): ?string => $value?->format('Y-m-d'),
        ];

        $changes = [];

        foreach ($normalize as $field => $cast) {
            $from = $cast($before[$field]);
            $to = $cast($card->{$field});

            if ($from !== $to) {
                $changes[$field] = [
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        if ($changes === []) {
            return;
        }

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: CardActivityType::CardUpdated,
            changes: $changes,
        );
    }

    /**
     * @param  Collection<int, Model>  $before
     * @param  Collection<int, Model>  $after
     */
    private function recordRelationChanges(
        Card $card,
        User $actor,
        CardActivityType $type,
        Collection $before,
        Collection $after,
        Closure $snapshot,

    ): void {
        $added = $after->diffKeys($before)->values();
        $removed = $before->diffKeys($after)->values();

        if ($added->isEmpty() && $removed->isEmpty()) {
            return;
        }

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: $type,
            changes: [
                'added' => $added->map($snapshot)->all(),
                'removed' => $removed->map($snapshot)->all(),
            ],
        );
    }
}
