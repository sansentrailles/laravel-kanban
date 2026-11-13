<?php

use App\Events\Kanban\CardRestored;
use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @return array<string, mixed>
 */
function restoreActivityPayload(PersistCardActivity $job): array
{
    return (new ReflectionProperty($job, 'payload'))->getValue($job);
}

it('restores an archived card and records a card.restored activity', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);
    $card->delete();

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.restore', $card->id))
        ->assertOk()
        ->assertJsonPath('card.id', $card->id)
        ->assertJsonPath('message', 'Карточка восстановлена');

    $this->assertNotSoftDeleted('kanban_cards', ['id' => $card->id]);

    $jobs = Queue::pushed(PersistCardActivity::class);

    expect($jobs)->toHaveCount(1);

    $payload = restoreActivityPayload($jobs->first());

    expect($payload['type'])->toBe('card.restored')
        ->and($payload['subject_type'])->toBe('card')
        ->and($payload['subject_id'])->toBe($card->id);

    $broadcasts = collect(Queue::pushed(BroadcastEvent::class))
        ->filter(fn (BroadcastEvent $job) => $job->event instanceof CardRestored);

    expect($broadcasts)->toHaveCount(1)
        ->and($broadcasts->first()->event->broadcastWith()['card']['id'])->toBe($card->id);
});

it('returns success without activity when the card is already active', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.restore', $card->id))
        ->assertOk()
        ->assertJsonPath('message', 'Карточка уже активна');

    Queue::assertNotPushed(PersistCardActivity::class);
    Queue::assertNotPushed(BroadcastEvent::class);
});

it('forbids a user outside the workspace', function () {
    /** @var TestCase $this */
    $card = Card::factory()->create();
    $card->delete();

    $outsider = User::factory()->create();

    $this
        ->actingAs($outsider)
        ->patchJson(route('boards.cards.restore', $card->id))
        ->assertForbidden();

    $this->assertSoftDeleted('kanban_cards', ['id' => $card->id]);
});

it('requires authentication', function () {
    $card = Card::factory()->create();
    $card->delete();

    $this->patchJson(route('boards.cards.restore', $card->id))
        ->assertUnauthorized();

});
