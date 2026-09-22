<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\Kanban\WorkspaceResource;
use App\Models\Kanban\Workspace;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $workspaces = Workspace::query()
            ->where('owner_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'workspaces' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function home(): Response
    {
        return Inertia::render('Home/Index', [
            'user' => [
                'name' => 'Ivan',
                'age' => 20,
            ],
        ]);
    }
}