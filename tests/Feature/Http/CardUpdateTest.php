<?php

use App\Models\Kanban\Card;
use App\Models\User;

it('forbids updating a card for a user outside the workspace', function () {
    $card = Card::factory()->create();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), [
            'title' => 'Test card edit',
        ]);

    $response->assertForbidden();
});

it('allows a workspace member to update a card', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), ['title' => 'New title'])
        ->assertOk();

    expect($card->refresh()->title)->toBe('New title');
});