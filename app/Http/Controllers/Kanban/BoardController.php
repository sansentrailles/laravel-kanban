<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Http\Resources\Kanban\BoardResource;
use App\Http\Resources\Kanban\WorkspaceResource;
use App\Models\Kanban\Board;
use App\Models\Kanban\Workspace;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    // Отображаем список воркспейсов пользоваеля
    // TODO: Добавить проверку права пользователя смотреть доску
    public function show(string $slug, string $uuid): Response
    {
        $user = Auth::user();

        $currentWorkspace = Workspace::where('slug', $slug)->firstOrFail();

        // Находим доску и жадно загружаем все необходимые связи
        $board = Board::where('workspace_id', $currentWorkspace->id)
            ->where('uuid', $uuid)
            ->with([
                'columns.cards',
                'columns.cards.labels',       // Колонки -> Карточки -> Метки
                'columns.cards.assignees',    // Колонки -> Карточки -> Исполнители
                'workspace.labels',           // Метки воркспейса (для выпадающих списков)
            ])
            ->firstOrFail();

        // Собираем данные для сайдбара (ИСПРАВЛЕННЫЙ ЗАПРОС)
        // Получаем все воркспейсы, где пользователь является owner ИЛИ member
        $workspaces = Workspace::where('owner_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('members')
            ->get();

        // Доски текущего воркспейса
        $boards = $currentWorkspace->boards()
            ->withCount('columns')
            ->ordered()
            ->get();

        return Inertia::render('Boards/Show', [
            'board' => (new BoardResource($board))->resolve(),
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve(),
            'currentWorkspace' => (new WorkspaceResource($currentWorkspace))->resolve(),
            'boards' => BoardResource::collection($boards),
            'unreadNotifications' => 0,
        ]);
    }
}
