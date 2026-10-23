<?php

namespace App\Http\Controllers\Kanban;

use App\Events\Kanban\ChecklistUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\ChecklistItemResource;
use App\Models\Kanban\Checklist;
use App\Models\Kanban\ChecklistItem;

class ChecklistItemController extends Controller
{
    public function store(Checklist $checklist)
    {
        $item = $checklist->items()->create(request()->validate([
            'content' => 'required|string|max:255',
        ]));

        broadcast(new ChecklistUpdated($checklist->card))->toOthers();

        return response()->json([
            'createdChecklistItem' => $item,
        ]);
    }

    public function toggle(Checklist $checklist, ChecklistItem $item)
    {
        $item->update([
            'is_completed' => ! $item->is_completed,
        ]);

        broadcast(new ChecklistUpdated($checklist->card))->toOthers();

        return response()->json([
            'updatedChecklistItems' => ChecklistItemResource::collection($checklist->items),
        ]);
    }

    public function delete(Checklist $checklist, ChecklistItem $item)
    {
        $card = $checklist->card;
        $item->delete();

        broadcast(new ChecklistUpdated($card))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }
}
