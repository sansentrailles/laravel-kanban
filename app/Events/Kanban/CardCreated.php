<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Http\Resources\Kanban\CardResource;
use App\Models\Kanban\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CardCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card
    ) {
        // Payload события — НОВАЯ, самодостаточная сущность: фронт пушит её
        // в массив карточек целиком, а Card.vue читает связи напрямую
        // (card.labels.length и т.п.). Без загрузки связей whenLoaded просто
        // опустит ключи, и рендер карточки упадёт на undefined.length.
        // Карточка только что создана — связи почти пустые, payload легкий.
        $this->card->load(['checklists.items', 'labels', 'assignees', 'comments.user']);
    }

    /**
     * Определяем, на какой канал отправлять событие.
     * Используем PrivateChannel, чтобы только участники доски могли его слышать.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->card->column->board->workspace_id),
        ];
    }

    /**
     * Имя события, которое будет слушать фронтенд.
     * По умолчанию Laravel использует имя класса, но можно задать явно.
     */
    public function broadcastAs(): string
    {
        return 'card.created';
    }

    /**
     * (Опционально) Форматируем данные перед отправкой, чтобы не тащить лишнее.
     */
    public function broadcastWith(): array
    {
        return [
            'card' => CardResource::make($this->card)->resolve(),
        ];
    }
}
