<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

readonly class CreateWorkspaceDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public int $ownerId,
    ) {}
}
