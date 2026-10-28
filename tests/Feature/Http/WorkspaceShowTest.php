<?php

use App\Models\Kanban\Board;
use App\Models\Kanban\Column;
use App\Models\Kanban\Workspace;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

it('renders the workspace page with ordered boards and counts', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user->id);

    Board::factory()->create([
        'workspace_id' => $workspace->id,
        'ord' => 200,
    ]);
    $first = Board::factory()->create([
        'workspace_id' => $workspace->id,
        'ord' => 100,
    ]);
    Column::factory()->count(2)->create(['board_id' => $first->id]);

    $this->actingAs($user)
        ->get(route('kanban.workspace', ['slug' => $workspace->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Workspaces/Show')
            ->where('currentWorkspace.members_count', 1)
            ->where('unreadNotifications', 0)
            ->has('boards', 2)
            // Плоский массив, а не { data: [...] }: иначе BoardList
            // в сайдбаре снова останется пустым.
            ->missing('boards.data')
            ->where('boards.0.uuid', $first->uuid)
            ->where('boards.0.columns_count', 2)
            ->etc()
        );
});

it('forbids viewing a workspace the user is not a member of', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($user)
        ->get(route('kanban.workspace', ['slug' => $workspace->slug]))
        ->assertForbidden();
});
