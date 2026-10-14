<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Http\Resources\Kanban\ColumnResource;
use App\Models\Kanban\Column;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ColumnUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Column $column
    ) {
        $this->column->load(['cards']);
    }

    /**
     * Определяем, на какой канал отправлять событие.
     * Используем PrivateChannel, чтобы только участники доски могли его слышать.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->column->workspace_id),
        ];
    }

    /**
     * Имя события, которое будет слушать фронтенд.
     * По умолчанию Laravel использует имя класса, но можно задать явно.
     */
    public function broadcastAs(): string
    {
        return 'column.updated';
    }

    /**
     * (Опционально) Форматируем данные перед отправкой, чтобы не тащить лишнее.
     */
    // public function broadcastWith(): array
    // {
    //     return [
    //         'card' => ColumnResource::make($this->column)->resolve(),
    //     ];
    // }
}
