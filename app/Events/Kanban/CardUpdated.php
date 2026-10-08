<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Models\Kanban\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card
    ) {}

    /**
     * Определяем, на какой канал отправлять событие.
     * Используем PrivateChannel, чтобы только участники доски могли его слышать.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.' . $this->card->column->board->workspace_id),
        ];
    }

    /**
     * Имя события, которое будет слушать фронтенд.
     * По умолчанию Laravel использует имя класса, но можно задать явно.
     */
    public function broadcastAs(): string
    {
        return 'card.updated';
    }

    /**
     * (Опционально) Форматируем данные перед отправкой, чтобы не тащить лишнее.
     */
    public function broadcastWith(): array
    {
        // Загружаем связи, чтобы фронтенд получил полные данные (метки, исполнители)
        $this->card->load(['labels', 'assignees']);
        
        return [
            'card' => \App\Http\Resources\Kanban\CardResource::make($this->card),
        ];
    }
}