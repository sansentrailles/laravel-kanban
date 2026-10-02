<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\MoveCardAction;
use App\Actions\Kanban\UpdateCardAction;
use App\DTO\Kanban\UpdateCardDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\CardOrderRequest;
use App\Http\Requests\Kanban\CardUpdateRequest;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    public function __construct(
        private MoveCardAction $moveCardAction,
        private UpdateCardAction $updateCardAction
    ) {}

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

    public function update(CardUpdateRequest $request, int $id)
    {
        $card = Card::findOrFail($id);

        $dto = UpdateCardDTO::fromRequest($request);

        $updatedCard = $this->updateCardAction->execute($card, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Обновлено',
            'data' => $updatedCard,
        ]);
    }
}
