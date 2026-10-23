<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Http\Resources\Kanban\ChecklistResource;
use App\Models\Kanban\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Любое изменение чек-листов карточки: создание/удаление чек-листа,
 * добавление/переключение/удаление пункта.
 *
 * Отправляем только массив чек-листов и счётчик — без остальных связей
 * карточки, чтобы payload оставался маленьким.
 */
final class ChecklistUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->card->column->board->workspace_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'checklist.updated';
    }

    public function broadcastWith(): array
    {
        $this->card->load(['checklists.items']);

        return [
            'card_id' => $this->card->id,
            'checklists' => ChecklistResource::collection($this->card->checklists)->resolve(),
            'checklists_count' => $this->card->checklists->count(),
        ];
    }
}
