<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Actions\User\UpdateLastVisitedAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\BoardResource;
use App\Http\Resources\Kanban\WorkspaceResource;
use App\Models\Kanban\Board;
use App\Models\Kanban\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function show(Workspace $workspace, Board $board, UpdateLastVisitedAction $updateLastVisitedAction): Response
    {
        // Страховка на уровне контроллера: доска должна принадлежать воркспейсу
        // из URL, а сам воркспейс — быть доступен пользователю. Иначе
        // currentWorkspace мог бы отдать участников и метки чужого воркспейса.
        Gate::authorize('view', $workspace);

        abort_unless($board->workspace_id === $workspace->id, 404);

        Gate::authorize('view', $board);

        $user = Auth::user();

        $updateLastVisitedAction->execute(
            $user,
            workspaceId: $workspace->id,
            boardId: $board->id
        );

        $workspaces = Workspace::where('owner_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('members')
            ->get();

        return Inertia::render('Boards/Show', [
            'board' => BoardResource::make($board->load([
                'columns.cards' => fn ($q) => $q
                    ->withCount(['comments', 'attachments', 'checklists'])
                    // Комментарии не грузим: их может быть много, а нужны они
                    // только в открытой карточке (GET cards.comments.index).
                    ->with(['labels', 'checklists.items', 'attachments', 'assignees']),
            ]))->resolve(),
            // members нужны AssigneePicker в модалке карточки.
            'currentWorkspace' => WorkspaceResource::make($workspace->load(['labels', 'members']))->resolve(),
            'boards' => BoardResource::collection($workspace->boards()->withCount(['columns'])->ordered()->get())->resolve(),
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve(),
            'unreadNotifications' => 0,
        ]);
    }
}
