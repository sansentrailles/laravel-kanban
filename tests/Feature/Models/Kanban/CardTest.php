<?php

use App\Models\Kanban\Card;

it('orders cards by ord', function () {
    $later = Card::factory()->create(['ord' => '2000000000.0000000000']);
    $earlier = Card::factory()->create(['ord' => '1000000000.0000000000']);

    expect(Card::ordered()->pluck('id')->all())->toBe([$earlier->id, $later->id]);
});

it('finds only cards that are overdue and not completed', function () {
    $overdue = Card::factory()->overdue()->create();
    Card::factory()->completed()->create(['due_date' => now()->subWeek()->toDateString()]);
    Card::factory()->create(['due_date' => now()->addWeek()->toDateString()]);
    Card::factory()->create(['due_date' => null]);

    expect(Card::overdue()->pluck('id')->all())->toBe([$overdue->id]);
});

it('stops treating a card as overdue once it is completed', function () {
    $card = Card::factory()->create([
        'due_date' => now()->subWeek()->toDateString(),
        'completed_at' => null,
    ]);

    expect($card->isOverdue())->toBeTrue();

    $card->markAsCompleted();
    $card->refresh();

    expect($card->isCompleted())->toBeTrue()
        ->and($card->completed_at)->not->toBeNull()
        ->and($card->isOverdue())->toBeFalse();
});
