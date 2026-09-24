<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

readonly class CreateColumnDTO
{
    public function __construct(
        public string $title,
        public int $boardId,
        public ?string $color,
        public ?int $wipLimit,
        public ?int $order,
    ) {}
}
