<?php

declare(strict_types=1);

namespace App\Policies\Kanban;

use App\Models\Kanban\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function delete(User $user, Attachment $attachment): bool
    {
        $workspace = $attachment->card->column->board->workspace;

        return $workspace->hasMember($user);
    }
}
