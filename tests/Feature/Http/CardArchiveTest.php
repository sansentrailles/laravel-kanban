<?php

use App\Events\Kanban\CardArchived;
use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

/**
 * @param  array<string, mixed>  $attributes
 */
function archiveCardFor(User $user, array $attributes = []): Card
{
    $card = Card::factory()->create($attributes);
    $card->column->board->workspace->members()->attach($user->id);

    return $card;
}

/**
 * @return array<string, mixed>
 */
function archiveActivityPayload(PersistCardActivity $job): array
{
    return (new ReflectionProperty($job, 'payload'))->getValue($job);
}

it('archives a card and records a card.archived activity', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = archiveCardFor($user);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.archive', $card->id))
        ->assertOk()
        ->assertJsonPath('message', 'Карточка в архиве');

    $this->assertSoftDeleted('kanban_cards', ['id' => $card->id]);

    $jobs = Queue::pushed(PersistCardActivity::class);

    expect($jobs)->toHaveCount(1);

    $payload = archiveActivityPayload($jobs->first());

    expect($payload['type'])->toBe('card.archived')
        ->and($payload['subject_type'])->toBe('card')
        ->and($payload['subject_id'])->toBe($card->id)
        ->and($payload['card_title'])->toBe($card->title);

    $broadcasts = collect(Queue::pushed(BroadcastEvent::class))
        ->filter(fn (BroadcastEvent $job) => $job->event instanceof CardArchived);

    expect($broadcasts)->toHaveCount(1)
        ->and($broadcasts->first()->event->broadcastWith())->toBe([
            'card_id' => $card->id,
            'column_id' => $card->column_id,
        ]);
});

it('forbids a user outside the workspace', function () {
    $card = Card::factory()->create();
    $outsider = User::factory()->create();

    $this
        ->actingAs($outsider)
        ->patchJson(route('boards.cards.archive', $card->id))
        ->assertForbidden();

    $this->assertNotSoftDeleted('kanban_cards', ['id' => $card->id]);
});

it('requires authentication', function () {
    $card = Card::factory()->create();

    $this
        ->patchJson(route('boards.cards.archive', $card->id))
        ->assertUnauthorized();
});

it('returns 404 when the card is already archived', function () {
    $user = User::factory()->create();
    $card = archiveCardFor($user);

    $card->delete();

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.archive', $card->id))
        ->assertNotFound();
});
