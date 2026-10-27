<?php

use App\Models\Kanban\Board;
use App\Models\User;

it('redirects to the last visited board with the correct route parameters', function () {
    // Регрессия: после переименования параметров маршрута на {workspace:slug}
    // и {board:uuid} дашборд продолжал передавать slug/uuid, и route() падал
    // с UrlGenerationException (500 на "/").
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->workspace->members()->attach($user->id);

    $user->update(['last_visited_board_id' => $board->id]);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('kanban.board', [
            'workspace' => $board->workspace->slug,
            'board' => $board->uuid,
        ]));
});

it('ignores a last visited board the user cannot access', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $user->update(['last_visited_board_id' => $board->id]);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('kanban.workspaces.index'));
});

it('redirects to the last visited workspace when no board is set', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->workspace->members()->attach($user->id);

    $user->update([
        'last_visited_board_id' => null,
        'last_visited_workspace_id' => $board->workspace_id,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('kanban.workspace', [
            'slug' => $board->workspace->slug,
        ]));
});

it('keeps redirecting to the board after revisiting it', function () {
    // Регрессия: повторный визит доски обнулял last_visited_board_id,
    // и дашборд начинал вести на воркспейс вместо доски.
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->workspace->members()->attach($user->id);

    $boardUrl = route('kanban.board', [
        'workspace' => $board->workspace->slug,
        'board' => $board->uuid,
    ]);

    $this->actingAs($user)->get($boardUrl)->assertOk();
    $this->actingAs($user)->get($boardUrl)->assertOk();

    $this->actingAs($user)->get('/')->assertRedirect($boardUrl);
});

it('redirects to the workspace after visiting the workspace page', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->workspace->members()->attach($user->id);

    $workspaceUrl = route('kanban.workspace', ['slug' => $board->workspace->slug]);

    $this->actingAs($user)
        ->get(route('kanban.board', [
            'workspace' => $board->workspace->slug,
            'board' => $board->uuid,
        ]))
        ->assertOk();

    $this->actingAs($user)->get($workspaceUrl)->assertOk();

    $this->actingAs($user)->get('/')->assertRedirect($workspaceUrl);
});
