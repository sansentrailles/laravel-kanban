<?php

declare(strict_types=1);

namespace App\Policies\Kanban;

use App\Models\Kanban\Board;
use App\Models\User;

class BoardPolicy
{
    /**
     * Создать колонку в доске может любой участник воркспейса этой доски
     * (право действия привязано к доске, т.к. колонка создаётся в её контексте).
     */
    public function createColumn(User $user, Board $board): bool
    {
        return $board->workspace->hasMember($user);
    }

    public function view(User $user, Board $board): bool
    {
        return $board->workspace->hasMember($user);
    }
}
