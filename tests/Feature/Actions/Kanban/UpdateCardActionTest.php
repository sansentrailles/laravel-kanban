<?php

use App\Actions\Kanban\UpdateCardAction;
use App\DTO\Kanban\UpdateCardDTO;
use App\Models\Kanban\Card;

it('updates only the passed attributes', function () {
    $card = Card::factory()->create(['title' => 'Old title', 'description' => null]);

    $updated = (new UpdateCardAction)->execute(
        $card,
        new UpdateCardDTO(['title' => 'New title']),
    );

    expect($updated->refresh())
        ->title->toBe('New title')
        ->description->toBeNull(); // untouched attribute stays as it was
});

it('returns the card unchanged when there is nothing to update', function () {
    $card = Card::factory()->create(['title' => 'Old title']);

    $returned = (new UpdateCardAction)->execute($card, new UpdateCardDTO);

    expect($returned)->toBe($card)
        ->and($card->fresh()->title)->toBe('Old title');
});
