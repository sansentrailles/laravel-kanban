<?php

declare(strict_types=1);

namespace App\Policies\Kanban;

use App\Models\Kanban\Card;
use App\Models\User;

class CardPolicy
{
    public function update(User $user, Card $card): bool
    {
        $workspace = $card->column->board->workspace;

        return $workspace->hasMember($user);
    }
}
