<?php

use App\Events\Kanban\ColumnUpdated;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

function attachUserToWorkspaceColumn(User $user, Column $column): void
{
    $column->board->workspace->members()->attach($user->id);
}

it('allows a workspace member to change the column color and queues a broadcast', function () {
    Queue::fake();

    $user = User::factory()->create();
    $column = Column::factory()->create(['color' => '#4b82f6']);
    attachUserToWorkspaceColumn($user, $column);

    $response = $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.update', $column->id), [
            'color' => '#10b981',
        ])
        ->assertOk();

    expect($column->refresh()->color)->toBe('#10b981');

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($column) {
        return $job->event instanceof ColumnUpdated
            && $job->event->column->id === $column->id;
    });

    // Фронт может сразу применить ответ: колонка приходит в пейлоаде
    expect($response->json('column.color'))->toBe('#10b981');
});

it('forbids changing the column color for a user outside the workspace', function () {
    $user = User::factory()->create();
    $column = Column::factory()->create(['color' => '#4b82f6']);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.update', $column->id), [
            'color' => '#10b981',
        ])
        ->assertForbidden();

    // Изоляция: цвет колонки не изменился
    expect($column->refresh()->color)->toBe('#4b82f6');
});

it('allows a workspace member to rename the column', function () {
    $user = User::factory()->create();
    $column = Column::factory()->create(['title' => 'Прежнее']);
    attachUserToWorkspaceColumn($user, $column);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.update', $column->id), [
            'title' => 'Новое название',
        ])
        ->assertOk();

    expect($column->refresh()->title)->toBe('Новое название');
});

it('rejects renaming a column to a title that already exists on the same board', function () {
    $user = User::factory()->create();
    // Две колонки одной доски: пытаемся вторую переименовать в название первой
    $column = Column::factory()->create(['title' => 'Оригинал']);
    $duplicate = Column::factory()->create(['board_id' => $column->board_id, 'title' => 'Другая']);
    attachUserToWorkspaceColumn($user, $duplicate);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.update', $duplicate->id), [
            'title' => 'Оригинал',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');

    // Валидация не дала переименовать
    expect($duplicate->refresh()->title)->toBe('Другая');
});

it('allows renaming a column to its own current title', function () {
    // ignore() в правиле unique: своё же название не считается дубликатом
    $user = User::factory()->create();
    $column = Column::factory()->create(['title' => 'Оригинал']);
    attachUserToWorkspaceColumn($user, $column);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.update', $column->id), [
            'title' => 'Оригинал',
        ])
        ->assertOk();

    expect($column->refresh()->title)->toBe('Оригинал');
});

it('rejects a color that is not a valid hex code', function () {
    Queue::fake();

    $user = User::factory()->create();
    $column = Column::factory()->create(['color' => '#4b82f6']);
    attachUserToWorkspaceColumn($user, $column);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.columns.update', $column->id), [
            'color' => 'red',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('color');

    // Валидация поймала ввод до мутации
    expect($column->refresh()->color)->toBe('#4b82f6');
    Queue::assertNotPushed(BroadcastEvent::class);
});
