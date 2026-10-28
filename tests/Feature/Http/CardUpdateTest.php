<?php

use App\Enums\Kanban\CardPriority;
use App\Models\Kanban\Card;
use App\Models\User;
use Tests\TestCase;

it('forbids updating a card for a user outside the workspace', function () {
    /** @var TestCase $this */
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
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), ['title' => 'New title'])
        ->assertOk();

    expect($card->refresh()->title)->toBe('New title');
});

it('updates the card priority', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), ['priority' => 'high'])
        ->assertOk()
        ->assertJsonPath('data.priority', 'high');

    expect($card->refresh()->priority)->toBe(CardPriority::High);
});

it('rejects the legacy misspelled priority value', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create(['priority' => CardPriority::Low]);

    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), ['priority' => 'hight'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('priority');

    expect($card->refresh()->priority)->toBe(CardPriority::Low);
});
