<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use App\Http\Resources\Kanban\CommentResource;
use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Новый комментарий. Отправляем только сам комментарий и счётчик,
 * чтобы не гонять всю карточку (лимит сообщения Reverb ~10 КБ).
 */
final class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment,
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
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->card->id,
            // user обязателен: Comment.vue читает comment.user.name без ?.
            'comment' => CommentResource::make($this->comment->loadMissing('user'))->resolve(),
            'comments_count' => $this->card->comments()->count(),
        ];
    }
}
