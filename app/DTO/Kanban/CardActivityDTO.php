<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

use App\Enums\Kanban\CardActivityType;

readonly class CardActivityDTO
{
    public function __construct(
        public CardActivityType $type,
        public int $cardId,
        public int $workspaceId,
        public int $boardId,
        public int $columnId,
        public string $cardTitle,
        public int $actorId,
        public string $actorName,
        public ?int $subjectId,
        public array $changes = [],
        public array $context = [],
    ) {}
}
