<?php

namespace App\Http\Controllers\Kanban;

use App\Events\Kanban\CardUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\ChecklistItemResource;
use App\Models\Kanban\Checklist;
use App\Models\Kanban\ChecklistItem;
use Illuminate\Support\Facades\Gate;

class ChecklistItemController extends Controller
{
    public function store(Checklist $checklist)
    {
        $item = $checklist->items()->create(request()->validate([
            'content' => 'required|string|max:255', 
        ]));

        $card = $checklist->card;
        $card->fresh()->load(['checklists.items', 'labels', 'assignees']);

        broadcast(new CardUpdated($checklist->card))->toOthers();

        return response()->json([
            'createdChecklistItem' => $item,
        ]);
    }

    public function toggle(Checklist $checklist, ChecklistItem $item)
    {
        $item->update([
            'is_completed' => ! $item->is_completed,
        ]);

        $card = $checklist->card;
        $card->fresh()->load(['checklists.items', 'labels', 'assignees']);

        broadcast(new CardUpdated($checklist->card))->toOthers();

        return response()->json([
            'updatedChecklistItems' => ChecklistItemResource::collection($checklist->items),
        ]);
    }
}
