<?php

use App\Events\Kanban\CommentCreated;
use App\Events\Kanban\CommentDeleted;
use App\Events\Kanban\CommentUpdated;
use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

function commentCardFor(User $user): Card
{
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    return $card;
}

it('broadcasts CommentCreated with the author and a fresh counter', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = commentCardFor($user);

    $this
        ->actingAs($user)
        ->postJson(route('cards.comments.store', $card->id), [
            'content' => 'Первый комментарий',
        ])
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card, $user) {
        if (! $job->event instanceof CommentCreated) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['comment']['content'] === 'Первый комментарий'
            // Автор обязателен: Comment.vue читает comment.user.name без ?.
            && $payload['comment']['user']['name'] === $user->name
            && $payload['comments_count'] === 1;
    });
});

it('broadcasts CommentUpdated with the new content', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = commentCardFor($user);
    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => 'Старый текст',
    ]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', [
            'card' => $card->id,
            'comment' => $comment->id,
        ]), [
            'content' => 'Новый текст',
        ])
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card) {
        if (! $job->event instanceof CommentUpdated) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['comment']['content'] === 'Новый текст';
    });
});

it('broadcasts CommentDeleted with the id and a fresh counter', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = commentCardFor($user);
    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => 'Удаляемый комментарий',
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.comments.delete', [
            'card' => $card->id,
            'comment' => $comment->id,
        ]))
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card, $comment) {
        if (! $job->event instanceof CommentDeleted) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['comment_id'] === $comment->id
            && $payload['comments_count'] === 0;
    });
});
