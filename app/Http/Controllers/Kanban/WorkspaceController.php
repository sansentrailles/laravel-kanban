<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateWorkspaceAction;
use App\Actions\User\UpdateLastVisitedAction;
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
    public function list(): Response|RedirectResponse
    {
        $user = Auth::user();

        $workspaces = $user->ownedWorkspaces()
            ->withCount('members')
            ->get()
            ->merge($user->workspaces()->withCount('members')->get())
            ->unique('id')
            ->values();

        // Отдельной страницы со списком нет: сразу открываем первый воркспейс.
        if ($workspaces->isNotEmpty()) {
            return redirect()->route('kanban.workspace', [
                'slug' => $workspaces->first()->slug,
            ]);
        }

        return Inertia::render('Workspaces/Index', [
            'workspaces' => [],
            'currentWorkspace' => null,
            'boards' => [],
            'unreadNotifications' => 0,
        ]);
    }

    public function show(string $slug): Response
    {
        $user = Auth::user();

        $workspace = Workspace::where('slug', $slug)
            ->with(['owner', 'members'])
            ->withCount('members')
            ->firstOrFail();

        $workspaces = $user->ownedWorkspaces()
            ->withCount('members')
            ->get()
            ->merge($user->workspaces()->withCount('members')->get())
            ->unique('id')
            ->values();

        Gate::authorize('view', $workspace);

        app(UpdateLastVisitedAction::class)->execute(
            $user,
            workspaceId: $workspace->id,
        );

        $boards = $workspace->boards()
            ->withCount('columns')
            ->ordered()
            ->get();

        return Inertia::render('Workspaces/Show', [
            'workspaces' => WorkspaceResource::collection($workspaces)->resolve(),
            'currentWorkspace' => (new WorkspaceResource($workspace))->resolve(),
            'boards' => BoardResource::collection($boards)->resolve(),
            // Уведомлений пока нет: не отдаём фронту фейковый счётчик.
            'unreadNotifications' => 0,
        ]);
    }

    public function store(StoreWorkspaceRequest $request): RedirectResponse
    {
        $dto = new CreateWorkspaceDTO(
            name: $request->validated('name'),
            description: $request->validated('description'),
            ownerId: $request->user()->id,
        );

        $workspace = $this->createWorkspaceAction->execute($dto);

        return redirect()->route('kanban.workspace', $workspace->slug)
            ->with('success', 'Воркспейс создан')
            ->with('workspace', $workspace);
    }
}
