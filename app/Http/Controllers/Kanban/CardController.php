<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateCardAction;
use App\Actions\Kanban\MoveCardAction;
use App\Actions\Kanban\UpdateCardAction;
use App\DTO\Kanban\CreateCardDTO;
use App\DTO\Kanban\UpdateCardDTO;
use App\Events\Kanban\CardCreated;
use App\Events\Kanban\CardUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\CardCreateRequest;
use App\Http\Requests\Kanban\CardOrderRequest;
use App\Http\Requests\Kanban\CardUpdateRequest;
use App\Http\Resources\Kanban\CardResource;
use App\Http\Resources\Kanban\ColumnResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    public function __construct(
        private readonly MoveCardAction $moveCardAction,
        private readonly UpdateCardAction $updateCardAction,
        private readonly CreateCardAction $createCardAction
    ) {}

    public function store(CardCreateRequest $reqeust, Column $column)
    {
        $dto = new CreateCardDTO(
            title: $reqeust->validated('title'),
            columnId: $column->id,
        );

        $lastCard = $column->cards()->reorder('ord', 'desc')->first();

        $card = $this->createCardAction->execute($dto);
        // Перемещаем в конец
        // TODO: Возможно лучше вычислить ORD и передевать в экшн
        $this->moveCardAction->execute($card, $column, $lastCard, null);

        $card->fresh(['labels', 'checklists', 'assignees', 'comments.user']);

        broadcast(new CardCreated($card))->toOthers();

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

        $this->moveCardAction->execute($card, $column, $prevCard, $nextCard);

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
