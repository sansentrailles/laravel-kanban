<?php

namespace App\Http\Controllers\Kanban;

use App\Events\Kanban\ChecklistUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreChecklistRequest;
use App\Models\Kanban\Card;
use App\Models\Kanban\Checklist;

class ChecklistController extends Controller
{
    public function store(StoreChecklistRequest $request, Card $card)
    {
        // Gate::authorize('update', $card);
        $checklist = $card->checklists()->create($request->validated());

        // Присылаем всем клиентам актуальный набор чек-листов карточки
        broadcast(new ChecklistUpdated($card))->toOthers();

        return response()->json([
            'success' => true,
            'createdChecklist' => [
                'id' => $checklist->id,
                'title' => $checklist->title,
                'items' => [], // Важно для фронтенда
            ],
        ]);
    }

    public function delete(Checklist $checklist)
    {
        $card = $checklist->card;
        $checklist->delete();

        broadcast(new ChecklistUpdated($card))->toOthers();

        return response()->json([
            'success' => true,
            'checklists' => $card->checklists,
        ]);
    }
}
