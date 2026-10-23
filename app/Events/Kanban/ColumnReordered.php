<?php

namespace App\Events\Kanban;

use App\Http\Resources\Kanban\ColumnResource;
use App\Models\Kanban\Board;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnReordered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private Board $board
    ) {}

    /**
     * Определяем, на какой канал отправлять событие.
     * Используем PrivateChannel, чтобы только участники доски могли его слышать.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->board->workspace_id),
        ];
    }

    /**
     * Имя события, которое будет слушать фронтенд.
     * По умолчанию Laravel использует имя класса, но можно задать явно.
     */
    public function broadcastAs(): string
    {
        return 'column.reordered';
    }

    /**
     * (Опционально) Форматируем данные перед отправкой, чтобы не тащить лишнее.
     */
    public function broadcastWith(): array
    {
        return [
            'columns' => ColumnResource::collection($this->board->columns)->resolve(),
        ];
    }
}
