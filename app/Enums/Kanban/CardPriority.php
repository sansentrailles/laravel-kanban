<?php

declare(strict_types=1);

namespace App\Enums\Kanban;

enum CardPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'hight';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::Low => 'Низкий',
            self::Medium => 'Средний',
            self::High => 'Высокий',
            self::Urgent => 'Срочный',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Low => '#10b981', // Green
            self::Medium => '#3b82f6', // Blue
            self::High => '#f59e0b', // Orange
            self::Urgent => '#ef4444', // Red
        };
    }

}