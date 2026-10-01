<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\MoveCardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\CardOrderRequest;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    public function __construct(
        private MoveCardAction $action
    ) {}

    public function orders(CardOrderRequest $request): JsonResponse
    {
        $card = Card::findOrFail($request->validated('cardId'));
        $column = Column::findOrFail($request->validated('columnId'));
        $prevCard = Card::find($request->validated('prevCardId'));
        $nextCard = Card::find($request->validated('nextCardId'));

        $this->action->execute($card, $column, $prevCard, $nextCard);

        return response()->json([
            'success' => true,
            'message' => 'Порядок обновлен',
        ]);
    }
}
