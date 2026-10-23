<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Actions\User\UpdateLastVisitedAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\BoardResource;
use App\Http\Resources\Kanban\LabelResource;
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
                    ->with(['labels', 'checklists.items', 'attachments', 'assignees', 'comments.user']),
            ]))->resolve(),
            'currentWorkspace' => WorkspaceResource::make($workspace->load('labels'))->resolve(),
            'boards' => BoardResource::collection($workspace->boards()->withCount(['columns'])->ordered()->get())->resolve(),
            'workspaceLabels' => LabelResource::collection($workspace->labels),
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve(),
            'unreadNotifications' => 0,
        ]);
    }
}
