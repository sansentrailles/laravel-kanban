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
    public function show(string $uuid): Response
    {
        $user = Auth::user();

        // 1. Находим доску и жадно загружаем все необходимые связи
        $board = Board::with([
            'columns.cards.labels',       // Колонки -> Карточки -> Метки
            'columns.cards.assignees',    // Колонки -> Карточки -> Исполнители
            'workspace.labels',           // Метки воркспейса
        ])->where('uuid', $uuid)->firstOrFail();

        // 2. Собираем данные для сайдбара (ИСПРАВЛЕННЫЙ ЗАПРОС)
        // Получаем все воркспейсы, где пользователь является owner ИЛИ member
        $workspaces = Workspace::where('owner_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('members')
            ->get();

        $currentWorkspace = $board->workspace;

        // Доски текущего воркспейса
        $boards = $currentWorkspace->boards()
            ->withCount('columns')
            ->ordered()
            ->get();

        // 3. Отправляем данные в Inertia
        return Inertia::render('Boards/Show', [
            'board' => (new BoardResource($board))->resolve(),
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve(),
            'currentWorkspace' => (new WorkspaceResource($currentWorkspace))->resolve(),
            'boards' => BoardResource::collection($boards),
            'unreadNotifications' => 0,
        ]);
    }
}
