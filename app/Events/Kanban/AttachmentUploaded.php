<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Http\Resources\Kanban\AttachmentResource;
use App\Models\Kanban\Attachment;
use App\Models\Kanban\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Новое вложение. Отправляем вложение и счётчик, чтобы не гонять
 * всю карточку (лимит сообщения Reverb ~10 КБ).
 */
final class AttachmentUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Attachment $attachment,
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
        return 'attachment.uploaded';
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->card->id,
            'attachment' => AttachmentResource::make($this->attachment)->resolve(),
            'attachments_count' => $this->card->attachments()->count(),
        ];
    }
}
