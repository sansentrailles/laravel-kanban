<?php

use App\Models\Kanban\Board;
use App\Models\Kanban\Column;
use App\Models\User;

it('allows a workspace member to create a column on the board', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->workspace->members()->attach($user->id);

    $response = $this
        ->actingAs($user)
        ->post(route('boards.columns.store', $board->id), [
            'title' => 'Новая колонка',
            'color' => '#4b82f6',
        ]);

    // store() отвечает redirect()-ом на изменение, проверяем результат вБД
    $response->assertRedirect();

    $this->assertDatabaseHas('kanban_columns', [
        'board_id' => $board->id,
        'title' => 'Новая колонка',
        'color' => '#4b82f6',
    ]);
});

it('forbids creating a column for a user outside the workspace', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $this
        ->actingAs($user)
        ->post(route('boards.columns.store', $board->id), [
            'title' => 'Взлом',
        ])
        ->assertForbidden();

    // Ничего не создано
    $this->assertDatabaseMissing('kanban_columns', [
        'board_id' => $board->id,
        'title' => 'Взлом',
    ]);
});

it('gives each new column an incrementing ord on the board', function () {
    // Регрессия на баг max('order') vs max('ord'): раньше каждая колонка
    // получала ord = 100, потому что атрибута 'order' не существует
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->workspace->members()->attach($user->id);

    $this->actingAs($user)->post(route('boards.columns.store', $board->id), ['title' => 'Первая']);
    $this->actingAs($user)->post(route('boards.columns.store', $board->id), ['title' => 'Вторая']);

    $ords = Column::where('board_id', $board->id)->orderBy('id')->pluck('ord')->values();

    expect($ords->count())->toBe(2)
        ->and($ords->unique()->count())->toBe(2); // две разные позиции
});
