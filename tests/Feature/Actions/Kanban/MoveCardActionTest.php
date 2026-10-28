<?php

use App\Actions\Kanban\MoveCardAction;
use App\Events\Kanban\CardsReordered;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

it('moves a card to an empty column with the base order', function () {
    $targetColumn = Column::factory()->create();
    $card = Card::factory()->create();

    $moved = (new MoveCardAction)->execute($card, $targetColumn, null, null);

    expect($moved->refresh())
        ->column_id->toBe($targetColumn->id)
        ->and(bccomp((string) $moved->ord, '1000000000.0000000000'))->toBe(0);
});

it('inserts a card before the first card when there is no previous card', function () {
    $first = Card::factory()->create(['ord' => '1000000000.0000000000']);

    $moved = Card::factory()->create();

    $result = (new MoveCardAction)->execute($moved, $first->column, null, $first);

    expect(bccomp((string) $result->ord, (string) $first->ord))->toBe(-1);
});

it('inserts a card after the last card when there is no next card', function () {
    $last = Card::factory()->create();

    $moved = Card::factory()->create();

    $result = (new MoveCardAction)->execute($moved, $last->column, $last, null);

    expect(bccomp((string) $result->ord, (string) $last->ord))->toBe(1);
});

it('inserts a card between two neighbors with their midpoint order', function () {
    $prev = Card::factory()->create(['ord' => '1000000000.0000000000']);
    $next = Card::factory()->create(['ord' => '1100000000.0000000000']);
    $moved = Card::factory()->create();

    $result = (new MoveCardAction)->execute($moved, $prev->column, $prev, $next);

    expect(bccomp((string) $result->ord, (string) $prev->ord))->toBe(1)
        ->and(bccomp((string) $result->ord, (string) $next->ord))->toBe(-1);
});

it('rejects moving a card into a column that is at its WIP limit', function () {
    $limited = Column::factory()->create(['wip_limit' => 1]);
    Card::factory()->create(['column_id' => $limited->id]);
    $card = Card::factory()->create(); // another column

    (new MoveCardAction)->execute($card, $limited, null, null);
})->throws(ValidationException::class);

it('keeps the card in its source column when the WIP limit blocks the move', function () {
    $limited = Column::factory()->create(['wip_limit' => 1]);
    $existing = Card::factory()->create(['column_id' => $limited->id]);
    $card = Card::factory()->create();

    try {
        (new MoveCardAction)->execute($card, $limited, null, null);
        $this->fail('ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($card->fresh())
            ->column_id->not->toBe($limited->id)
            ->and($existing->fresh()->column_id)->toBe($limited->id);
    }
});

it('allows reordering within a column that is at its WIP limit', function () {
    $limited = Column::factory()->create(['wip_limit' => 1]);
    $card = Card::factory()->create(['column_id' => $limited->id]);

    $reordered = (new MoveCardAction)->execute($card, $limited, null, null);

    expect($reordered->refresh()->column_id)->toBe($limited->id);
});

it('allows moving into a column that has free WIP capacity', function () {
    $limited = Column::factory()->create(['wip_limit' => 2]);
    Card::factory()->create(['column_id' => $limited->id]);
    $card = Card::factory()->create();

    $moved = (new MoveCardAction)->execute($card, $limited, null, null);

    expect($moved->refresh()->column_id)->toBe($limited->id);
});

it('rebalances the column when the midpoint collides with a neighbor', function () {
    Queue::fake();

    $prev = Card::factory()->create(['ord' => '1000000000.0000000000']);
    $next = Card::factory()->create([
        'column_id' => $prev->column_id,
        'ord' => '1000000000.0000000001',
    ]);
    $moved = Card::factory()->create();

    $result = (new MoveCardAction)->execute($moved, $prev->column, $prev, $next);

    expect(bccomp((string) $result->ord, (string) $prev->fresh()->ord, 10))->toBe(1)
        ->and(bccomp((string) $result->ord, (string) $next->fresh()->ord, 10))->toBe(-1);

    $orders = $prev->column->cards()->orderBy('ord')->pluck('ord')->map(fn ($ord) => (string) $ord);

    expect($orders->unique()->count())->toBe($orders->count());

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($prev) {
        return $job->event instanceof CardsReordered
            && $job->event->column->id === $prev->column_id
            && count($job->event->cards) === 3;
    });
});

it('rebalances instead of producing a non-positive order at the top', function () {
    Queue::fake();

    $first = Card::factory()->create(['ord' => '100000000.0000000000']);
    $moved = Card::factory()->create();

    $result = (new MoveCardAction)->execute($moved, $first->column, null, $first);

    expect(bccomp((string) $result->ord, '0', 10))->toBe(1)
        ->and(bccomp((string) $result->ord, (string) $first->fresh()->ord, 10))->toBe(-1);
});

it('uses the current neighbor order even when the passed models are stale', function () {
    $prev = Card::factory()->create(['ord' => '1000000000.0000000000']);
    $next = Card::factory()->create([
        'column_id' => $prev->column_id,
        'ord' => '1100000000.0000000000',
    ]);
    $moved = Card::factory()->create();

    // Пока запрос ждал блокировку, сосед переехал.
    $next->update(['ord' => '1200000000.0000000000']);

    $result = (new MoveCardAction)->execute($moved, $prev->column, $prev, $next);

    // Среднее по свежим ord: (1.0e9 + 1.2e9) / 2 = 1.1e9.
    // По устаревшей модели next (1.1e9) вышло бы 1.05e9.
    expect(bccomp((string) $result->ord, '1100000000.0000000000', 10))->toBe(0);
});
