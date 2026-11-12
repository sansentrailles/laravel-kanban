<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Карточка отправлена в архив. Держим только скалярные id: с SerializesModels
 * воркер попытался бы восстановить уже soft-deleted модель, firstOrFail()
 * бросил бы исключение, и broadcast молча потерялся бы.
 */
final class CardArchived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $cardId,
        public int $columnId,
        public int $workspaceId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->workspaceId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'card.archived';
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->cardId,
            'column_id' => $this->columnId,
        ];
    }
}
