<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateWorkspaceAction;
use App\DTO\Kanban\CreateWorkspaceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreWorkspaceRequest;
use App\Http\Resources\Kanban\BoardResource;
use App\Http\Resources\Kanban\WorkspaceResource;
use App\Models\Kanban\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly CreateWorkspaceAction $createWorkspaceAction
    ) {}

    // Отображаем список воркспейсов пользоваеля
    public function index(): Response
    {
        $user = Auth::user();

        $workspaces = $user->ownedWorspaces()
            ->withCount('members')
            ->get()
            ->merge($user->workspaces()->withCount('members')->get())
            ->unique('id')
            ->values();

        return Inertia::render('Workspaces/Index', [
            'workspaces' => WorkspaceResource::collection($workspaces),
        ]);
    }

    /**
     * Создание нового воркспейса
     */
    public function store(StoreWorkspaceRequest $request): RedirectResponse
    {
        $dto = new CreateWorkspaceDTO(
            name: $request->validated('name'),
            description: $request->validated('description'),
            ownerId: $request->user()->id,
        );

        $workspace = $this->createWorkspaceAction->execute($dto);

        return redirect()->route('workspaces.show', $workspace->slug);
    }

    public function show(string $slug): Response
    {
        $workspace = Workspace::where('slug', $slug)
            ->with(['owner', 'members'])
            ->withCount('members')
            ->firstOrFail();

        Gate::authorize('view', $workspace);

        $boards = $workspace->boards()
            ->withCount('columns')
            ->ordered()
            ->get();

        return Inertia::render('Workspace/Show', [
            'workspace' => new WorkspaceResource($workspace),
            'boards' => BoardResource::collection($boards),
        ]);
    }
}