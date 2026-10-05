<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

class UpdateLastVisitedAction
{
    /**
     * Обновляет последние посещенные сущности только при их изменении.
     */
    public function execute(User $user, ?int $workspaceId = null, ?int $boardId = null): void
    {
        $updates = [];
        $updates['last_visited_board_id'] = null;

        // Проверяем изменение воркспейса
        if ($workspaceId !== null && $user->last_visited_workspace_id !== $workspaceId) {
            $updates['last_visited_workspace_id'] = $workspaceId;
        }

        // Проверяем изменение доски
        if ($boardId !== null && $user->last_visited_board_id !== $boardId) {
            $updates['last_visited_board_id'] = $boardId;
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }
}
