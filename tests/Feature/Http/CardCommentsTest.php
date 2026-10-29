<?php

use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;
use App\Models\User;

it('returns the comments of a card in chronological order', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $otherCard = Card::factory()->create(['column_id' => $card->column_id]);

    $older = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => 'Первый',
    ]);
    $older->forceFill(['created_at' => now()->subHour()])->save();

    $newer = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => 'Второй',
    ]);

    Comment::factory()->create([
        'card_id' => $otherCard->id,
        'user_id' => $user->id,
        'content' => 'Комментарий другой карточки',
    ]);

    $this->actingAs($user)
        ->getJson(route('cards.comments.index', $card->id))
        ->assertOk()
        ->assertJsonCount(2, 'comments')
        ->assertJsonPath('comments_count', 2)
        ->assertJsonPath('comments.0.id', $older->id)
        ->assertJsonPath('comments.0.content', 'Первый')
        ->assertJsonPath('comments.0.user.name', $user->name)
        ->assertJsonPath('comments.1.id', $newer->id);
});

it('forbids reading comments of a card outside the workspace', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $this->actingAs($user)
        ->getJson(route('cards.comments.index', $card->id))
        ->assertForbidden();
});
