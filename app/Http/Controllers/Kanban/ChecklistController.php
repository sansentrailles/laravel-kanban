<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\RecordCardActivityAction;
use App\Enums\Kanban\CardActivityType;
use App\Events\Kanban\ChecklistUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreChecklistRequest;
use App\Models\Kanban\Card;
use App\Models\Kanban\Checklist;
use Illuminate\Support\Facades\Gate;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly RecordCardActivityAction $recordCardActivityAction
    ) {}

    public function store(StoreChecklistRequest $request, Card $card)
    {
        $checklist = $card->checklists()->create($request->validated());

        // Присылаем всем клиентам актуальный набор чек-листов карточки
        broadcast(new ChecklistUpdated($card))->toOthers();

        $board = $card->column->board;
        $actor = $request->user();

        $this->recordCardActivityAction->executeForCard(
            card: $card,
            actor: $actor,
            type: CardActivityType::ChecklistCreated,
            subjectId: $checklist->id,
            changes: [
                'title' => $checklist->title,
            ],
            context: [],
        );

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
        Gate::authorize('delete', $checklist);

        $card = $checklist->card;
        $checklist->delete();

        broadcast(new ChecklistUpdated($card))->toOthers();

        return response()->json([
            'success' => true,
            'checklists' => $card->checklists,
        ]);
    }
}
