<?php

use App\Models\Kanban\Card;
use App\Models\Kanban\CardActivity;
use App\Models\User;

/**
 * Создаёт запись журнала напрямую, минуя очередь.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeCardActivity(Card $card, array $attributes = []): CardActivity
{
    $card->loadMissing('column.board');

    return CardActivity::query()->create([
        'schema_version' => CardActivity::SCHEMA_VERSION,
        'event_id' => (string) Str::uuid(),
        'type' => 'card.created',
        'workspace_id' => $card->column->board->workspace_id,
        'board_id' => $card->column->board_id,
        'column_id' => $card->column_id,
        'card_id' => $card->id,
        'card_title' => $card->title,
        'subject_type' => 'card',
        'subject_id' => $card->id,
        'actor' => ['id' => 1, 'name' => 'Actor'],
        'changes' => [],
        'context' => [],
        'created_at' => now(),
        ...$attributes,
    ]);
}

beforeEach(function () {
    CardActivity::query()->delete();
});

it('returns the activity of a card newest first', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $otherCard = Card::factory()->create(['column_id' => $card->column_id]);

    $older = makeCardActivity($card, [
        'type' => 'card.created',
        'created_at' => now()->subMinutes(5),
    ]);

    $newer = makeCardActivity($card, [
        'type' => 'comment.created',
        'subject_type' => 'comment',
        'subject_id' => 10,
        'changes' => [
            'content' => ['length' => 11, 'preview' => 'New comment'],
        ],
        'created_at' => now()->subMinute(),
    ]);

    makeCardActivity($otherCard, ['created_at' => now()]);

    $this
        ->actingAs($user)
        ->getJson(route('cards.activity.index', $card->id))
        ->assertOk()
        ->assertJsonCount(2, 'activities')
        ->assertJsonPath('activities.0.id', (string) $newer->_id)
        ->assertJsonPath('activities.1.id', (string) $older->_id)
        ->assertJsonPath('next_cursor', null);
})->group('mongo');

it('maps the stored activity to the api payload', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $createdAt = now()->startOfSecond();

    makeCardActivity($card, [
        'type' => 'card.updated',
        'actor' => ['id' => $user->id, 'name' => $user->name],
        'changes' => [
            'title' => ['from' => 'Old', 'to' => 'New'],
        ],
        'created_at' => $createdAt,
    ]);

    $this->actingAs($user)
        ->getJson(route('cards.activity.index', $card->id))
        ->assertOk()
        ->assertJsonPath('activities.0.type', 'card.updated')
        ->assertJsonPath('activities.0.type_label', 'Изменение карточки')
        ->assertJsonPath('activities.0.actor.id', $user->id)
        ->assertJsonPath('activities.0.actor.name', $user->name)
        ->assertJsonPath('activities.0.subject_type', 'card')
        ->assertJsonPath('activities.0.changes.title.from', 'Old')
        ->assertJsonPath('activities.0.changes.title.to', 'New')
        ->assertJsonPath('activities.0.created_at', $createdAt->toIso8601String());
})->group('mongo');

it('paginates activity with a cursor', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $first = makeCardActivity($card, ['created_at' => now()->subMinutes(2)]);
    $second = makeCardActivity($card, ['created_at' => now()->subMinute()]);
    $third = makeCardActivity($card, ['created_at' => now()]);

    $pageOne = $this->actingAs($user)
        ->getJson(route('cards.activity.index', [
            'card' => $card->id,
            'per_page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'activities')
        ->assertJsonPath('activities.0.id', (string) $third->_id)
        ->assertJsonPath('activities.1.id', (string) $second->_id);

    $cursor = $pageOne->json('next_cursor');

    expect($cursor)->not->toBeNull();

    $this->actingAs($user)
        ->getJson(route('cards.activity.index', [
            'card' => $card->id,
            'per_page' => 2,
            'cursor' => $cursor,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'activities')
        ->assertJsonPath('activities.0.id', (string) $first->_id)
        ->assertJsonPath('next_cursor', null);
})->group('mongo');

it('returns an empty list when the card has no activity', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->getJson(route('cards.activity.index', $card->id))
        ->assertOk()
        ->assertJsonCount(0, 'activities')
        ->assertJsonPath('next_cursor', null);
})->group('mongo');

it('rejects an invalid cursor', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->getJson(route('cards.activity.index', [
            'card' => $card->id,
            'cursor' => 'not-a-cursor',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cursor');
})->group('mongo');

it('forbids a user outside the workspace', function () {
    $card = Card::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->getJson(route('cards.activity.index', $card->id))
        ->assertForbidden();
})->group('mongo');

it('requires authentication', function () {
    $card = Card::factory()->create();

    $this->getJson(route('cards.activity.index', $card->id))
        ->assertUnauthorized();
})->group('mongo');
