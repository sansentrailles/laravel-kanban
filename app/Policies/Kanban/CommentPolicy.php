<?php

declare(strict_types=1);

namespace App\Policies\Kanban;

use App\Models\Kanban\Comment;
use App\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $comment->card->column->board->workspace->hasMember($user) && $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->card->column->board->workspace->hasMember($user)
            && $comment->user_id === $user->id;
    }
}
