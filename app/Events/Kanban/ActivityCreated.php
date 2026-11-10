<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Новая запись в журнале активности карточки. Отправляем готовый payload
 * ресурса, чтобы открытая модалка патчила историю без перезапроса.
 */
final class ActivityCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $activity
     */
    public function __construct(
        public int $workspaceId,
        public int $cardId,
        public array $activity,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->workspaceId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'activity.created';
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->cardId,
            'activity' => $this->activity,
        ];
    }
}
