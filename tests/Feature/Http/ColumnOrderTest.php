<?php

use App\Events\Kanban\ColumnReordered;
use App\Models\Kanban\Board;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

it('reorders the columns of a board for a workspace member', function () {
    /** @var TestCase $this */
    // Arrange: доска, три колонки с известным ord, участник
    /** @var User $user */
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $first = Column::factory()->create(['board_id' => $board->id, 'ord' => 100]);
    $second = Column::factory()->create(['board_id' => $board->id, 'ord' => 200]);
    $third = Column::factory()->create(['board_id' => $board->id, 'ord' => 300]);

    $board->workspace->members()->attach($user->id);

    // Act: новый порядок задается порядком массива
    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.orders', $board->id), [
            'ids' => [$third->id, $first->id, $second->id],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    // Assert: состояние бд
    $this->assertDatabaseHas('kanban_columns', ['id' => $third->id, 'ord' => 100]);
    $this->assertDatabaseHas('kanban_columns', ['id' => $first->id, 'ord' => 200]);
    $this->assertDatabaseHas('kanban_columns', ['id' => $second->id, 'ord' => 300]);
});

it('forbids reordering columns for a user outside the workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $board = Board::factory()->create();
    Column::factory()->count(3)->create(['board_id' => $board->id]);

    // До запроса: свежий запрос к БД, не кеш связи
    $before = $board->columns()->pluck('ord', 'id')->all();

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.orders', $board->id), [
            'ids' => $board->columns()->pluck('id')->all(),
        ])
        ->assertForbidden();

    expect($board->columns()->pluck('ord', 'id')->all())->toEqual($before);
});

it('returns 404 when the ids list contains a column from another board', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $mine = Column::factory(2)->create(['board_id' => $board->id]);

    $otherBoard = Board::factory()->create();
    $foreign = Column::factory()->create(['board_id' => $otherBoard->id]);

    // Пользователь должен быть участником ОБОИХ воркспейсов,
    // иначе запрос отсановится на 403
    $board->workspace->members()->attach($user->id);
    $otherBoard->workspace->members()->attach($user->id);

    $beforeMine = $board->columns()->pluck('ord', 'id')->all();
    $beforeForeign = $foreign->ord;

    $ids = [
        ...$mine->pluck('id')->all(),
        $foreign->id,
    ];

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.orders', $board->id), ['ids' => $ids])
        ->assertNotFound();

    expect($board->columns()->pluck('ord', 'id')->all())->toEqual($beforeMine);
});

it('returns 404 when the ids list is incomplete', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $columns = Column::factory()->count(3)->create(['board_id' => $board->id]);

    $board->workspace->members()->attach($user->id);

    $before = $board->columns()->pluck('ord', 'id')->all();

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.orders', $board->id), [
            'ids' => $columns->take(2)->pluck('id')->all(),
        ])
        ->assertNotFound();

    expect($board->columns()->pluck('ord', 'id')->all())->toEqual($before);
});

it('returns 404 when the ids list contains duplicates', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $columns = Column::factory()->count(3)->create(['board_id' => $board->id]);
    $board->workspace->members()->attach($user->id);

    $before = $board->columns()->pluck('ord', 'id')->all();

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.orders', $board->id), [
            'ids' => [$columns[0]->id, $columns[0]->id, $columns[1]->id],
        ])
        ->assertNotFound();

    expect($board->columns()->pluck('ord', 'id')->all())->toEqual($before);
});

it('broadcasts the new column order so other client can sync', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $board = Board::factory()->create();

    $first = Column::factory()->create(['board_id' => $board->id, 'ord' => 100]);
    $second = Column::factory()->create(['board_id' => $board->id, 'ord' => 200]);
    $third = Column::factory()->create(['board_id' => $board->id, 'ord' => 300]);

    $board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.orders', $board->id), [
            'ids' => [$third->id, $first->id, $second->id],
        ])
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($third, $first, $second) {
        if (! $job->event instanceof ColumnReordered) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return array_column($payload['columns'], 'id') === [$third->id, $first->id, $second->id]
            && array_column($payload['columns'], 'ord') === [100, 200, 300];
    });
});
