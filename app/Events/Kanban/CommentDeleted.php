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
 * Комментарий удалён. Модель уже удалена, поэтому передаём только id
 * и актуальный счётчик — этого достаточно, чтобы убрать его из стора.
 */
final class CommentDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $commentId,
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
        return 'comment.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->card->id,
            'comment_id' => $this->commentId,
            'comments_count' => $this->card->comments()->count(),
        ];
    }
}
