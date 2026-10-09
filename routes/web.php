<?php

// use App\Http\Controllers\HomeController;

use App\Events\TestReverbEvent;
use App\Http\Controllers\Kanban\BoardController;
use App\Http\Controllers\Kanban\CardController;
use App\Http\Controllers\Kanban\ChecklistController;
use App\Http\Controllers\Kanban\ColumnController;
use App\Http\Controllers\Kanban\DashboardController;
use App\Http\Controllers\Kanban\LabelController;
use App\Http\Controllers\Kanban\WorkspaceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/workspaces}', [WorkspaceController::class, 'list'])->name('kanban.workspaces.index');
    Route::get('/workspaces/{slug}', [WorkspaceController::class, 'show'])->name('kanban.workspace');
    Route::get('/workspaces/{slug}/boards/{uuid}', [BoardController::class, 'show'])->name('kanban.board');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('kanban.workspaces.store');

    // Создание колонки
    Route::post('/boards/{board}/columns', [ColumnController::class, 'store'])->name('boards.columns.store');
    Route::patch('/columns/orders', [ColumnController::class, 'orders'])->name('boards.columns.orders');
    Route::patch('/cards/orders', [CardController::class, 'orders'])->name('boards.cards.orders');
    Route::patch('/cards/{card}', [CardController::class, 'update'])->name('boards.cards.update');

    Route::post('/card/{card}/checklist', [ChecklistController::class, 'store'])->name('cards.checklist.store');
    Route::delete('/card/checklist/{checklist}', [ChecklistController::class, 'delete'])->name('cards.checklist.delete');

    // Воркспейсы
    Route::post('/workspaces/{workspace}/labels', [LabelController::class, 'store'])->name('workspaces.labels.store');
    // Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    // Route::get('/workspaces/{slug}', [WorkspaceController::class, 'show'])->name('workspaces.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-reverb', function () {
    TestReverbEvent::dispatch();

    return 'Событие отправлено в очередь! Проверьте консоль браузера.';
});

require __DIR__.'/auth.php';
