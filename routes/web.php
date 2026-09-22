<?php

// use App\Http\Controllers\HomeController;

use App\Http\Controllers\Kanban\BoardController;
use App\Http\Controllers\Kanban\WorkspaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
// use App\Http\Controllers\WorkspaceController;
// use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [WorkspaceController::class, 'index'])->name('dashboard');
Route::get('/boards/{uuid}', [BoardController::class, 'show'])->name('kanban.board');


// Route::get('/', [ProjectController::class, 'home'])->name('projects.home');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
// Route::get('/', [WorkspaceController::class, 'index'])->name('dashboard');

// Route::get('/', function () {
//     return redirect()->route('boards.show', 1);
// });


// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/', [WorkspaceController::class, 'index'])->name('dashboard');

//     // Воркспейсы
//     Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
//     Route::get('/workspaces/{slug}', [WorkspaceController::class, 'show'])->name('workspaces.show');
// });

// Route::get('/', function () {
//     return redirect()->route('boards.show', 1);
// });

// Route::get('/boards/{board}', function (int $boardId) {
//     $user = Auth::user();

//     // 1. Находим доску и жадно загружаем все необходимые связи
//     $board = Board::with([
//         'columns.cards.labels',       // Колонки -> Карточки -> Метки
//         'columns.cards.assignees',    // Колонки -> Карточки -> Исполнители
//         'workspace.labels',           // Метки воркспейса
//     ])->findOrFail($boardId);

//     // 2. Собираем данные для сайдбара (ИСПРАВЛЕННЫЙ ЗАПРОС)
//     // Получаем все воркспейсы, где пользователь является owner ИЛИ member
//     $workspaces = Workspace::where('owner_id', $user->id)
//         ->orWhereHas('members', function ($query) use ($user) {
//             $query->where('user_id', $user->id);
//         })
//         ->withCount('members')
//         ->get();

//     $currentWorkspace = $board->workspace;

//     // Доски текущего воркспейса
//     $boards = $currentWorkspace->boards()
//         ->withCount('cards') 
//         ->ordered()
//         ->get();

//     // 3. Отправляем данные в Inertia
//     return Inertia::render('Boards/Show', [ // Убедись, что путь к компоненту верный (например, 'Kanban/Boards/Show')
//         'board' => new BoardResource($board),
//         'workspaces' => WorkspaceResource::collection($workspaces),
//         'currentWorkspace' => new WorkspaceResource($currentWorkspace),
//         'boards' => BoardResource::collection($boards),
//         'unreadNotifications' => 0,
//     ]);
// })->name('boards.show');

// Route::get('/boards/{board}', function ($boardId) {
//     // Временно возвращаем мок-данные
//     // Позже заменим на контроллер
//     return Inertia::render('Boards/Show', [
//         'title' => 'Abc',
//         'auth' => [
//             'user' => [
//                 'id' => 1,
//                 'name' => 'Alex Johnson',
//                 'email' => 'alex@example.com',
//             ],
//         ],
//         'workspaces' => [
//             [
//                 'id' => 1,
//                 'name' => 'My Workspace',
//                 'plan' => 'Pro',
//                 'members_count' => 5,
//             ],
//         ],
//         'currentWorkspace' => [
//             'id' => 1,
//             'name' => 'My Workspace',
//             'plan' => 'Pro',
//             'members_count' => 5,
//         ],
//         'boards' => [
//             [
//                 'id' => 1,
//                 'name' => 'Project Orion',
//                 'color' => '#3b82f6',
//                 'cards_count' => 8,
//             ],
//             [
//                 'id' => 2,
//                 'name' => 'Marketing Q4',
//                 'color' => '#10b981',
//                 'cards_count' => 12,
//             ],
//         ],
//         'unreadNotifications' => 3,
//     ]);
// })->name('boards.show');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
