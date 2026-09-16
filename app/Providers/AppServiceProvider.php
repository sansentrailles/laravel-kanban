<?php

namespace App\Providers;

use App\Models\Kanban\Workspace;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Inertia::share([
            // Данные текущего пользователя
            'auth' => fn () => [
                'user' => Auth::user()
            ],
            // flash-сообщение
            'flash' => fn () => session('message')
        ]);

        Gate::policy(Workspace::class, WorkspacePolicy::class);
    }
}
