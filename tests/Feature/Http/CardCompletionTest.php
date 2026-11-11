<?php

use App\Events\Kanban\CardUpdated;
use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

/**
 * @param  array<string, mixed>  $attributes
 */
function completionCardFor(User $user, array $attributes = []): Card
{
    $card = Card::factory()->create($attributes);
    $card->column->board->workspace->members()->attach($user->id);

    return $card;
}

/**
 * @return array<string, mixed>
 */
function activityPayloadOf(PersistCardActivity $job): array
{
    return (new ReflectionProperty($job, 'payload'))->getValue($job);
}

it('completes a card and records a card.completed activity', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = completionCardFor($user, ['completed_at' => null]);

    $response = $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.completion', $card->id), [
            'completed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('card.id', $card->id)
        ->assertJsonPath('message', 'Карточка завершена');

    expect($response->json('card.completed_at'))->not->toBeNull()
        ->and($card->refresh()->completed_at)->not->toBeNull();

    Queue::assertPushed(
        BroadcastEvent::class,
        fn (BroadcastEvent $job) => $job->event instanceof CardUpdated,
    );

    $jobs = Queue::pushed(PersistCardActivity::class);

    expect($jobs)->toHaveCount(1);

    $payload = activityPayloadOf($jobs->first());

    expect($payload['type'])->toBe('card.completed')
        ->and($payload['subject_type'])->toBe('card')
        ->and($payload['subject_id'])->toBe($card->id)
        ->and($payload['actor'])->toBe(['id' => $user->id, 'name' => $user->name]);
});

it('reopens a completed card and records a card.reopened activity', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = completionCardFor($user, ['completed_at' => now()]);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.completion', $card->id), [
            'completed' => false,
        ])
        ->assertOk()
        ->assertJsonPath('card.completed_at', null)
        ->assertJsonPath('message', 'Карточка возвращена в работу');

    expect($card->refresh()->completed_at)->toBeNull();

    $payload = activityPayloadOf(Queue::pushed(PersistCardActivity::class)->first());

    expect($payload['type'])->toBe('card.reopened')
        ->and($payload['subject_type'])->toBe('card')
        ->and($payload['subject_id'])->toBe($card->id);
});

it('does not record activity when the state is unchanged', function () {
    Queue::fake();

    $user = User::factory()->create();
    $completedAt = now()->startOfSecond();
    $card = completionCardFor($user, ['completed_at' => $completedAt]);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.completion', $card->id), [
            'completed' => true,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Статус уже актуален');

    Queue::assertNotPushed(PersistCardActivity::class);
    Queue::assertNotPushed(BroadcastEvent::class);

    expect($card->refresh()->completed_at->toIso8601String())
        ->toBe($completedAt->toIso8601String());
});

it('rejects an invalid completed flag', function () {
    $user = User::factory()->create();
    $card = completionCardFor($user, ['completed_at' => null]);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.completion', $card->id), [
            'completed' => 'nope',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('completed');
});

it('forbids a user outside the workspace', function () {
    $card = Card::factory()->create(['completed_at' => null]);
    $outsider = User::factory()->create();

    $this
        ->actingAs($outsider)
        ->patchJson(route('boards.cards.completion', $card->id), [
            'completed' => true,
        ])
        ->assertForbidden();

    expect($card->refresh()->completed_at)->toBeNull();
});

it('requires authentication', function () {
    $card = Card::factory()->create(['completed_at' => null]);

    $this
        ->patchJson(route('boards.cards.completion', $card->id), [
            'completed' => true,
        ])
        ->assertUnauthorized();
});
