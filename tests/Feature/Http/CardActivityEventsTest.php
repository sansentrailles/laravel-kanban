<?php

use App\Events\Kanban\ActivityCreated;
use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\Card;
use App\Models\Kanban\CardActivity;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $attributes
 * @return array<string, mixed>
 */
function activityPayload(Card $card, array $attributes = []): array
{
    $card->loadMissing('column.board');

    return [
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
    ];
}

beforeEach(function () {
    // Коллекция в тестовой Mongo-базе (MONGO_DB_DATABASE=laravel_mongo_testing)
    CardActivity::query()->delete();
});

it('broadcasts ActivityCreated after persisting the activity', function () {
    Queue::fake();

    $card = Card::factory()->create();

    (new PersistCardActivity(activityPayload($card)))->handle();

    $broadcasts = collect(Queue::pushed(BroadcastEvent::class))
        ->filter(fn (BroadcastEvent $job) => $job->event instanceof ActivityCreated);

    expect($broadcasts)->toHaveCount(1);

    $payload = $broadcasts->first()->event->broadcastWith();

    expect($payload['card_id'])->toBe($card->id)
        ->and($payload['activity']['type'])->toBe('card.created')
        ->and($payload['activity']['type_label'])->toBe('Создание карточки')
        ->and($payload['activity']['actor'])->toBe(['id' => 1, 'name' => 'Actor'])
        ->and($payload['activity']['created_at'])->not->toBeNull();
})->group('mongo');

it('does not broadcast twice for the same event id', function () {
    Queue::fake();

    DB::connection('mongodb')->getCollection('card_activities')->createIndex(
        ['event_id' => 1],
        ['unique' => true, 'name' => 'event_id_unique'],
    );

    $card = Card::factory()->create();
    $payload = activityPayload($card);

    (new PersistCardActivity($payload))->handle();
    (new PersistCardActivity($payload))->handle();

    $broadcasts = collect(Queue::pushed(BroadcastEvent::class))
        ->filter(fn (BroadcastEvent $job) => $job->event instanceof ActivityCreated);

    expect($broadcasts)->toHaveCount(1);
})->group('mongo');
