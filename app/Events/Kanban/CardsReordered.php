<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Models\Kanban\Column;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Переиндексация ord в колонке: при вставке в узкую щель или в начало
 * MoveCardAction перенумеровывает карточки, и клиентам нужно обновить
 * ord всех карточек колонки, иначе их локальный порядок разъедется.
 *
 * Payload минимальный: только id и ord, без полных карточек.
 */
final class CardsReordered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, array{id: int, ord: string}>  $cards
     */
    public function __construct(
        public Column $column,
        public array $cards,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->column->board->workspace_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'cards.reordered';
    }

    public function broadcastWith(): array
    {
        return [
            'column_id' => $this->column->id,
            'cards' => $this->cards,
        ];
    }
}
