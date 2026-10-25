<?php

use App\Actions\Kanban\CreateColumnAction;
use App\DTO\Kanban\CreateColumnDTO;
use App\Models\Kanban\Board;
use Tests\TestCase;

it('creates a column with the provided attributes', function () {
    /** @var TestCase $this */
    $board = Board::factory()->create();

    $column = (new CreateColumnAction)->execute(
        new CreateColumnDTO(title: 'To Do', boardId: $board->id, color: '#ff0000', wipLimit: 5, order: 3),
    );

    $this->assertDatabaseHas('kanban_columns', [
        'id' => $column->id,
        'board_id' => $board->id,
        'title' => 'To Do',
        'color' => '#ff0000',
        'wip_limit' => 5,
        'ord' => 3,
    ]);
});

it('creates a column with nullable attributes omitted', function () {
    $board = Board::factory()->create();

    $column = (new CreateColumnAction)->execute(
        new CreateColumnDTO(title: 'Backlog', boardId: $board->id, color: null, wipLimit: null, order: null),
    );

    expect($column->refresh())
        ->color->toBeNull()
        ->wip_limit->toBeNull()
        ->ord->toBe(0); // default of the migration when order is not provided
});
