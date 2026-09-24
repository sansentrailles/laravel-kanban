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
                'columns.cards.labels',       // Колонки -> Карточки -> Метки
                'columns.cards.assignees',    // Колонки -> Карточки -> Исполнители
                'workspace.labels',           // Метки воркспейса (для выпадающих списков)
            ])
            ->firstOrFail();
        // $board = Board::with([
        //     'columns.cards.labels',       // Колонки -> Карточки -> Метки
        //     'columns.cards.assignees',    // Колонки -> Карточки -> Исполнители
        //     'workspace.labels',           // Метки воркспейса
        // ])->where('uuid', $uuid)->firstOrFail();

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

    // public function show2(Request $request, string $workspaceSlug, string $boardUuid)
    // {
    //     $user = $request->user();

    //     // 1. Находим воркспейс по slug
    //     $workspace = Workspace::where('slug', $workspaceSlug)->firstOrFail();

    //     // 2. БЕЗОПАСНОСТЬ: Находим доску СТРОГО в рамках этого воркспейса по uuid
    //     $board = Board::where('workspace_id', $workspace->id)
    //         ->where('uuid', $boardUuid)
    //         ->with([
    //             'columns.cards.labels',       // Колонки -> Карточки -> Метки
    //             'columns.cards.assignees',    // Колонки -> Карточки -> Исполнители
    //             'workspace.labels',           // Метки воркспейса (для выпадающих списков)
    //         ])
    //         ->firstOrFail(); // Если не найдено, вернет 404

    //     // 3. Авторизация (проверяем права на доску или воркспейс)
    //     $this->authorize('view', $board);

    //     // 4. Обновляем историю посещений (Action из предыдущего шага)
    //     app(UpdateLastVisitedAction::class)->execute(
    //         $user,
    //         workspaceId: $workspace->id,
    //         boardId: $board->id
    //     );

    //     // 5. Собираем данные для сайдбара (AppLayout)
    //     $workspaces = Workspace::where('owner_id', $user->id)
    //         ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
    //         ->withCount('members')
    //         ->get();

    //     $boards = $workspace->boards()->withCount('cards')->ordered()->get();

    //     // 6. Отправляем в Inertia
    //     return Inertia::render('Kanban/Boards/Show', [
    //         'board' => new BoardResource($board),
    //         'workspaces' => WorkspaceResource::collection($workspaces),
    //         'currentWorkspace' => new WorkspaceResource($workspace),
    //         'boards' => BoardResource::collection($boards),
    //         'unreadNotifications' => 0,
    //     ]);
    // }
}
