<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

readonly class CreateCardDTO
{
    public function __construct(
        public string $title,
        public int $columnId,
    ) {}
}
