<?php

use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\Card;
use App\Models\Kanban\CardActivity;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

it('queues a card.created activity with the full payload', function () {
    /** @var TestCase $this */

    Queue::fake();

    $user = User::factory()->create();
    $column = Column::factory()->create();
    $column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('boards.cards.store', $column->id), [
            'title' => 'New activity card',
        ])
        ->assertOk();
    
    $card = Card::query()
        ->where('title', 'New activity card')
        ->firstOrFail();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($card, $column, $user) {
        $property = new ReflectionProperty($job, 'payload');
        $payload = $property->getValue($job);

        return $payload['type'] === 'card.created'
            && $payload['schema_version'] === CardActivity::SCHEMA_VERSION
            && $payload['card_id'] === $card->id
            && $payload['column_id'] === $column->id
            && $payload['board_id'] === $column->board_id
            && $payload['workspace_id'] === $column->board->workspace_id
            && $payload['card_title'] === 'New activity card'
            && $payload['subject_type'] === 'card'
            && $payload['actor'] === ['id' => $user->id, 'name' => $user->name]
            && Str::isUuid($payload['event_id'])
            && $payload['changes'] === []
            && $payload['context']['priority'] === $card->priority?->value;
    });
    
});

it('stores an activity only once for the same event id', function () {
    DB::connection('mongodb')->getCollection('card_activities')->createIndex(
        ['event_id' => 1],
        ['unique' => true, 'name' => 'event_id_unique']
    );

    $eventId = (string) Str::uuid();

    $payload = [
        'schema_version' => CardActivity::SCHEMA_VERSION,
        'event_id' => $eventId,
        'type' => 'card.created',
        'workspace_id' => 1,
        'board_id' => 1,
        'column_id' => 1,
        'card_id' => 1,
        'card_title' => 'New card',
        'subject_type' => 'card',
        'subject_id' => 1,
        'actor' => ['id' => 1, 'name' => 'Test'],
        'changes' => [],
        'context' => [],
        'created_at' => now(),
    ];

    try {
        (new PersistCardActivity($payload))->handle();
        (new PersistCardActivity($payload))->handle();

        expect(CardActivity::query()->where('event_id', $eventId)->count())->toBe(1);
    } finally {
        CardActivity::query()->where('event_id', $eventId)->delete();
    }
})->group('mongo');