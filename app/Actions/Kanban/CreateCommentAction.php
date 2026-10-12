<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\CreateCommentDTO;
use App\Models\Kanban\Comment;
use Illuminate\Support\Facades\DB;

class CreateCommentAction
{
    public function execute(CreateCommentDTO $dto): Comment
    {
        return DB::transaction(function () use ($dto) {
            return Comment::create([
                'card_id' => $dto->cardId,
                'user_id' => $dto->userId,
                'content' => $dto->content,
            ]);
        });
    }
}
