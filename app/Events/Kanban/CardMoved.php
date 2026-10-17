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

final class CardMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card
    ) {
        $this->card->load(['checklists.items', 'labels', 'assignees', 'comments.user']);
    }

    /**
     * Канал доски: все участники workspace доски получают перемещение.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->card->column->board->workspace_id),
        ];
    }

    /**
     * Имя события, которое слушает фронтенд (Echo: `.card.moved`).
     */
    public function broadcastAs(): string
    {
        return 'card.moved';
    }

    /**
     * Отправляем полный ресурс карточки: клиенту нужны column_id и ord,
     * чтобы переставить карточку на своей доске.
     */
    public function broadcastWith(): array
    {
        return [
            'card' => CardResource::make($this->card)->resolve(),
        ];
    }
}
