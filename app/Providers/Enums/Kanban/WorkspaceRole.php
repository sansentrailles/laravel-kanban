<?php

declare(strict_types=1);

namespace App\Enums\Kanban;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Владелец',
            self::Admin => 'Администратор',
            self::Member => 'Участник',
            self::Viewer => 'Наблюдатель',
        };
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageBoards(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Member], true);
    }

    public function canDeleteWorkspace(): bool
    {
        return $this === self::Owner;
    }
}

