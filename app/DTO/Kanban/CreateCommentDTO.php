<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

readonly class CreateCommentDTO
{
    public function __construct(
        public string $content,
        public int $cardId,
        public int $userId,
    ) {}
}
