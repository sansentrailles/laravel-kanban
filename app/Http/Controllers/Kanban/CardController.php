<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateCardAction;
use App\Actions\Kanban\MoveCardAction;
use App\Actions\Kanban\RecordCardActivityAction;
use App\Actions\Kanban\UpdateCardAction;
use App\DTO\Kanban\CardActivityDTO;
use App\DTO\Kanban\CreateCardDTO;
use App\DTO\Kanban\UpdateCardDTO;
use App\Enums\Kanban\CardActivityType;
use App\Events\Kanban\CardCreated;
use App\Events\Kanban\CardMoved;
use App\Events\Kanban\CardUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\CardCreateRequest;
use App\Http\Requests\Kanban\CardOrderRequest;
use App\Http\Requests\Kanban\CardUpdateRequest;
use App\Http\Resources\Kanban\CardResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function __construct(
        private readonly MoveCardAction $moveCardAction,
        private readonly UpdateCardAction $updateCardAction,
        private readonly CreateCardAction $createCardAction,
        private readonly RecordCardActivityAction $recordCardActivityAction
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

        $card->fresh(['labels', 'checklists', 'assignees', 'comments.user']);

        broadcast(new CardCreated($card))->toOthers();

        $actor = $request->user();
        $board = $column->board;

        $this->recordCardActivityAction->execute(new CardActivityDTO(
            type: CardActivityType::CardCreated,
            cardId: $card->id,
            workspaceId: $board->workspace_id,
            boardId: $board->id,
            columnId: $column->id,
            cardTitle: $card->title,
            actorId: $actor->id,
            actorName: $actor->name,
            subjectId: $card->id,
            context: [
                'priority' => $card->priority?->value,
            ]
        ));

        return response()->json([
            'card' => new CardResource($card),
        ]);
    }

    public function orders(CardOrderRequest $request): JsonResponse
    {
        $card = Card::findOrFail($request->validated('cardId'));
        $column = Column::findOrFail($request->validated('columnId'));
        $prevCard = Card::find($request->validated('prevCardId'));
        $nextCard = Card::find($request->validated('nextCardId'));

        $movedCard = $this->moveCardAction->execute($card, $column, $prevCard, $nextCard);

        broadcast(new CardMoved($movedCard))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Порядок обновлен',
        ]);
    }

    public function update(CardUpdateRequest $request, Card $card)
    {
        // $this->authorize('update', $card);

        $data = $request->validated();
        $dto = UpdateCardDTO::fromRequest($request);

        // Обновляем основные поля через Action
        $updatedCard = $this->updateCardAction->execute($card, $dto);

        // Синхронизируем метки (если они были переданы и прошли валидацию)
        if (isset($data['label_ids'])) {
            $updatedCard->labels()->sync($data['label_ids']);
        }

        // Синхронизируем исполнителей (по аналогии)
        if (isset($data['assignee_ids'])) {
            $updatedCard->assignees()->sync($data['assignee_ids']);
        }

        // ВОЗВРАЩАЕМ карточку с загруженными связями!
        // Это позволит фронтенду получить актуальные данные (например, имена исполнителей, цвета меток)
        $updatedCard->load([
            'labels',
            'assignees',
        ]);

        event(new CardUpdated($updatedCard));

        return response()->json([
            'success' => true,
            'message' => 'Обновлено',
            'data' => $updatedCard,
            'labels' => $updatedCard->labels,
        ]);
    }
}
