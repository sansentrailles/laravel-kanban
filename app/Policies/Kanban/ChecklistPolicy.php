<?php

declare(strict_types=1);

namespace App\Policies\Kanban;

use App\Models\Kanban\Checklist;
use App\Models\User;

class ChecklistPolicy
{
    public function update(User $user, Checklist $checklist): bool
    {
        return $checklist->card->column->board->workspace->hasMember($user);
    }

    public function delete(User $user, Checklist $checklist): bool
    {
        return $checklist->card->column->board->workspace->hasMember($user);
    }
}
