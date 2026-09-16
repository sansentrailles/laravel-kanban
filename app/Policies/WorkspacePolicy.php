<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kanban\Workspace;
use App\Models\User;

final class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        $role = $workspace->getRoleForUser($user);

        return $role?->canManageMembers() ?? false;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwner($user);
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        $role = $workspace->getRoleForUser($user);

        return $role?->canManageMembers() ?? false;
    }

}
