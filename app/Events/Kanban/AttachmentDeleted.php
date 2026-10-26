<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Models\Kanban\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Вложение удалено. Отправляем id и счётчик, чтобы не гонять всю карточку
 * (лимит сообщения Reverb ~10 КБ).
 */
final class AttachmentDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card,
        public int $attachmentId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.'.$this->card->column->board->workspace_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attachment.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->card->id,
            'attachment_id' => $this->attachmentId,
            'attachments_count' => $this->card->attachments()->count(),
        ];
    }
}
