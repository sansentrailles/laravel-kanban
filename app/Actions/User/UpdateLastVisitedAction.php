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

        // Проверяем изменение воркспейса
        if ($workspaceId !== null && $user->last_visited_workspace_id !== $workspaceId) {
            $updates['last_visited_workspace_id'] = $workspaceId;
        }

        if ($boardId !== null) {
            // Проверяем изменение доски
            if ($user->last_visited_board_id !== $boardId) {
                $updates['last_visited_board_id'] = $boardId;
            }
        } elseif ($user->last_visited_board_id !== null) {
            // Открыли страницу воркспейса: дашборд должен вести туда, а не на старую доску.
            $updates['last_visited_board_id'] = null;
        }

        if (! empty($updates)) {
            $user->update($updates);
        }
    }
}
