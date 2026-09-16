<?php

declare(strict_types=1);

namespace App\Enums\Kanban;

enum BoardVisibility: string
{
    case Private = 'private';
    case Workspace = 'workspace';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Только участники',
            self::Workspace => 'Весь воркспейс',
        };
    }
}
