<?php

// use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
// use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/', function () {
    return redirect()->route('boards.show', 1);
});

Route::get('/boards/{board}', function ($boardId) {
    // Временно возвращаем мок-данные
    // Позже заменим на контроллер
    return Inertia::render('Boards/Show', [
        'auth' => [
            'user' => [
                'id' => 1,
                'name' => 'Alex Johnson',
                'email' => 'alex@example.com',
            ]
        ],
        'workspaces' => [
            [
                'id' => 1,
                'name' => 'My Workspace',
                'plan' => 'Pro',
                'members_count' => 5,
            ]
        ],
        'currentWorkspace' => [
            'id' => 1,
            'name' => 'My Workspace',
            'plan' => 'Pro',
            'members_count' => 5,
        ],
        'boards' => [
            [
                'id' => 1,
                'name' => 'Project Orion',
                'color' => '#3b82f6',
                'cards_count' => 8,
            ],
            [
                'id' => 2,
                'name' => 'Marketing Q4',
                'color' => '#10b981',
                'cards_count' => 12,
            ]
        ],
        'unreadNotifications' => 3,
    ]);
})->name('boards.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
