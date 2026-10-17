<?php

use App\Actions\Kanban\CreateWorkspaceAction;
use App\DTO\Kanban\CreateWorkspaceDTO;
use App\Enums\Kanban\WorkspaceRole;
use App\Models\Kanban\Workspace;
use App\Models\User;

it('creates a workspace with an auto-generated slug and saved description', function () {
    $user = User::factory()->create();

    $workspace = (new CreateWorkspaceAction)->execute(
        new CreateWorkspaceDTO(name: 'Design Team', description: 'All design work', ownerId: $user->id),
    );

    expect($workspace)->toBeInstanceOf(Workspace::class)
        ->name->toBe('Design Team')
        ->description->toBe('All design work')
        ->slug->toBe('design-team')
        ->owner_id->toBe($user->id);
});

it('attaches the owner as a member with the Owner role', function () {
    $user = User::factory()->create();

    $workspace = (new CreateWorkspaceAction)->execute(
        new CreateWorkspaceDTO(name: 'Acme', description: null, ownerId: $user->id),
    );

    $this->assertDatabaseHas('kanban_workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
});

it('generates a unique slug when a workspace with the same name exists', function () {
    $user = User::factory()->create();
    $action = new CreateWorkspaceAction;

    $first = $action->execute(new CreateWorkspaceDTO(name: 'Acme', description: null, ownerId: $user->id));
    $second = $action->execute(new CreateWorkspaceDTO(name: 'Acme', description: null, ownerId: $user->id));

    expect($first->slug)->toBe('acme')
        ->and($second->slug)->toBe('acme-1');
});
