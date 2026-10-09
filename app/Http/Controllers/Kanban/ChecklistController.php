<?php

namespace App\Http\Controllers\Kanban;

use App\Events\Kanban\CardUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreChecklistRequest;
use App\Models\Kanban\Card;
use App\Models\Kanban\Checklist;
use Illuminate\Support\Facades\Gate;

class ChecklistController extends Controller
{
    public function store(StoreChecklistRequest $request, Card $card)
    {
        // Gate::authorize('update', $card);
        $checklist = $card->checklists()->create($request->validated());

        broadcast(new CardUpdated($card))->toOthers();

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

        $card->fresh()->load(['checklists.items', 'labels', 'assignees']);

        broadcast(new CardUpdated($card))->toOthers();

        return response()->json([
            'success' => true,
            'checklists' => $card->checklists,
        ]);
    }
}
