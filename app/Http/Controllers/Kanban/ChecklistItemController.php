<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\RecordCardActivityAction;
use App\Enums\Kanban\CardActivityType;
use App\Events\Kanban\ChecklistUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreChecklistItemRequest;
use App\Http\Resources\Kanban\ChecklistItemResource;
use App\Models\Kanban\Checklist;
use App\Models\Kanban\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChecklistItemController extends Controller
{
    public function __construct(
        private RecordCardActivityAction $recordCardActivityAction
    ) {}

    public function store(StoreChecklistItemRequest $request, Checklist $checklist)
    {
        $item = $checklist->items()->create($request->validated());

        broadcast(new ChecklistUpdated($checklist->card))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $checklist->card,
            actor: $request->user(),
            type: CardActivityType::ChecklistItemCreated,
            subjectId: $item->id,
            context: [
                'content' => $item->content,
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ],
        );

        return response()->json([
            'createdChecklistItem' => $item,
        ]);
    }

    public function toggle(Request $request, Checklist $checklist, ChecklistItem $item)
    {
        Gate::authorize('update', $checklist);

        $wasCompleted = (bool) $item->is_completed;

        $item->update([
            'is_completed' => ! $wasCompleted,
        ]);

        broadcast(new ChecklistUpdated($checklist->card))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $checklist->card,
            actor: $request->user(),
            type: CardActivityType::ChecklistItemToggled,
            subjectId: $item->id,
            changes: [
                'is_completed' => [
                    'from' => $wasCompleted,
                    'to' => (bool) $item->is_completed,
                ],
            ],
            context: [
                'content' => $item->content,
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ],
        );

        return response()->json([
            'updatedChecklistItems' => ChecklistItemResource::collection($checklist->items),
        ]);
    }

    public function delete(Request $request, Checklist $checklist, ChecklistItem $item)
    {
        Gate::authorize('delete', $checklist);

        $card = $checklist->card;
        $itemId = $item->id;
        $itemContent = $item->content;
        $item->delete();

        broadcast(new ChecklistUpdated($card))->toOthers();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $request->user(),
            type: CardActivityType::ChecklistItemDeleted,
            subjectId: $itemId,
            context: [
                'content' => $itemContent,
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ],
        );

        return response()->json([
            'success' => true,
        ]);
    }
}
