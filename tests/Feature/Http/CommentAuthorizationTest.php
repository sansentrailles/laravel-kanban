<?php

use App\Models\Kanban\Card;
use App\Models\Kanban\Comment;
use App\Models\User;
use Tests\TestCase;

it('allows a workspace member to create a comment', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.comments.store', ['card' => $card->id]), [
            'content' => 'New comment',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('kanban_comments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);

    $comment = $card->comments()->firstOrFail();

    expect($comment->content)->toBe('New comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('allows the author to update a comment', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', ['card' => $card->id, 'comment' => $comment->id]), [
            'content' => 'Edited comment text',
        ])
        ->assertOk();

    $comment->refresh();
    expect($comment->content)->toBe('Edited comment text')
        ->and($comment->is_edited)->toBeTrue();
});

it('forbids updating a comment of another user', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $card = Card::factory()->create();
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $otherUser->id, 'card_id' => $card->id]);

    $card->column->board->workspace->members()->attach([$user->id, $otherUser->id]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', ['card' => $card->id, 'comment' => $comment->id]), [
            'content' => 'Edited comment text',
        ])
        ->assertForbidden();

    $comment->refresh();
    expect($comment->content)->toBe('Comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('forbids updating a comment for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', ['card' => $card->id, 'comment' => $comment->id]), [
            'content' => 'Edited comment text',
        ])
        ->assertForbidden();

    $comment->refresh();
    expect($comment->content)->toBe('Comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('returns 404 when the comment belongs to another card', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $otherCard = Card::factory()->create(['column_id' => $card->column_id]);
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', ['card' => $otherCard->id, 'comment' => $comment->id]), [
            'content' => 'Edited comment text',
        ])
        ->assertNotFound();

    $comment->refresh();
    expect($comment->content)->toBe('Comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('allows the author to delete a comment', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.comments.delete', ['comment' => $comment->id, 'card' => $card->id]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('kanban_comments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);
});

it('forbids deleting a comment of another user', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $card = Card::factory()->create();
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $card->column->board->workspace->members()->attach([$user->id, $otherUser->id]);

    $this
        ->actingAs($otherUser)
        ->deleteJson(route('cards.comments.delete', ['comment' => $comment->id, 'card' => $card->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('kanban_comments', ['id' => $comment->id]);

    $comment->refresh();
    expect($comment->content)->toBe('Comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('forbids deleting a comment for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.comments.delete', ['comment' => $comment->id, 'card' => $card->id]))
        ->assertForbidden();

    $comment->refresh();
    expect($comment->content)->toBe('Comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('returns 404 when deleting a comment of another card', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $otherCard = Card::factory()->create(['column_id' => $card->column_id]);
    $comment = Comment::factory()->create(['content' => 'Comment', 'user_id' => $user->id, 'card_id' => $card->id]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.comments.delete', ['comment' => $comment->id, 'card' => $otherCard->id]))
        ->assertNotFound();

    $comment->refresh();
    expect($comment->content)->toBe('Comment')
        ->and($comment->is_edited)->toBeFalse();
});

it('forbids commenting for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $this
        ->actingAs($user)
        ->postJson(route('cards.comments.store', $card->id), [
            'content' => 'Привет',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('kanban_comments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);
});
