<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Models\Kanban\Board;
use App\Models\Kanban\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->last_visited_board_id) {
            $board = Board::with('workspace')->find($user->last_visited_board_id);

            if ($board && $board->workspace->hasMember($user)) {
                return redirect()->route('kanban.board', [
                    'slug' => $board->workspace->slug,
                    'uuid' => $board->uuid,
                ]);
            }
        }

        if ($user->last_visited_workspace_id) {
            $workspace = Workspace::find($user->last_visited_workspace_id);

            if ($workspace && $workspace->hasMember($user)) {
                return redirect()->route('kanban.workspace', [
                    'slug' => $workspace->slug,
                ]);
            }
        }

        return redirect()->route('kanban.workspaces.index');
    }
}
