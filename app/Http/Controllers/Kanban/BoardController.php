<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Actions\User\UpdateLastVisitedAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\ArchivedCardResource;
use App\Http\Resources\Kanban\BoardResource;
use App\Http\Resources\Kanban\WorkspaceResource;
use App\Models\Kanban\Board;
use App\Models\Kanban\Card;
use App\Models\Kanban\Workspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function show(Workspace $workspace, Board $board, UpdateLastVisitedAction $updateLastVisitedAction): Response
    {
        $this->authorizeBoard($workspace, $board);

        $user = Auth::user();

        $updateLastVisitedAction->execute(
            $user,
            workspaceId: $workspace->id,
            boardId: $board->id
        );

        return Inertia::render('Boards/Show', [
            'board' => BoardResource::make($board->load([
                'columns.cards' => fn ($q) => $q
                    ->withCount(['comments', 'attachments', 'checklists'])
                    ->with(['labels', 'checklists.items', 'attachments', 'assignees']),
            ]))->resolve(),
            ...$this->layoutProps($user, $workspace),
        ]);
    }

    public function archive(Workspace $workspace, Board $board): Response
    {
        $this->authorizeBoard($workspace, $board);

        $archivedCards = Card::onlyTrashed()
            ->whereHas('column', fn (Builder $query) => $query->where('board_id', $board->id))
            ->with(['column' => fn ($query) => $query->withTrashed()])
            ->orderByDesc('deleted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Boards/Archive', [
            'board' => BoardResource::make($board)->resolve(),
            'archivedCards' => ArchivedCardResource::collection($archivedCards)->response()->getData(true),
            ...$this->layoutProps(Auth::user(), $workspace),
        ]);
    }

    private function authorizeBoard(Workspace $workspace, Board $board): void
    {
        Gate::authorize('view', $workspace);

        abort_unless($board->workspace_id === $workspace->id, 404);

        Gate::authorize('view', $board);
    }

    /**
     * Пропсы для AppLayout (сайдбар) на любой странице воркспейса
     *
     * @return array<string, mixed>
     */
    private function layoutProps(User $user, Workspace $workspace): array
    {
        $workspaces = Workspace::where('owner_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('members')
            ->get();

        return [
            'currentWorkspace' => WorkspaceResource::make($workspace->load(['labels', 'members']))->resolve(),
            'boards' => BoardResource::collection($workspace->boards()->withCount(['columns'])->ordered()->get())->resolve(),
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve(),
            'unreadNotifications' => 0,
        ];
    }
}
