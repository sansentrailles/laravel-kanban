<?php

declare(strict_types=1);

namespace App\Policies\Kanban;

use App\Models\Kanban\Column;
use App\Models\User;

class ColumnPolicy
{
    /**
     * Обновление колонки (цвет, название, WIP-лимит)
     * доступно любому участнику воркспейса этой колонки.
     */
    public function update(User $user, Column $column): bool
    {
        return $column->board->workspace->hasMember($user);
    }

    /**
     * Создание карточки в колонке — то же право на изменение доски,
     * что и у колонок: участник воркспейса, к которому относится колонка.
     */
    public function createCard(User $user, Column $column): bool
    {
        return $column->board->workspace->hasMember($user);
    }
}
