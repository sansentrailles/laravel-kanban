<?php

use App\Actions\Kanban\CreateCardAction;
use App\DTO\Kanban\CreateCardDTO;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;

it('creates a card in the given column', function () {
    $column = Column::factory()->create();

    $card = (new CreateCardAction)->execute(
        new CreateCardDTO(title: 'Fix the bug', columnId: $column->id),
    );

    $card->refresh();

    expect($card)->toBeInstanceOf(Card::class)
        ->title->toBe('Fix the bug')
        ->column_id->toBe($column->id);
});
