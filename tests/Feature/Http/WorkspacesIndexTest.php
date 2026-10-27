<?php

use App\Models\Kanban\Workspace;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

it('redirects the workspaces index to the first workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->get(route('kanban.workspaces.index'))
        ->assertRedirect(route('kanban.workspace', ['slug' => $workspace->slug]));
});

it('renders an empty state when the user has no workspaces', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('kanban.workspaces.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Workspaces/Index')
            ->where('workspaces', [])
            ->etc()
        );
});
