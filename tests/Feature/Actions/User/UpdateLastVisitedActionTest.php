<?php

declare(strict_types=1);

use App\Actions\User\UpdateLastVisitedAction;
use App\Models\Kanban\Board;
use App\Models\User;

it('stores both the workspace and the board on a board visit', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    (new UpdateLastVisitedAction)->execute(
        $user,
        workspaceId: $board->workspace_id,
        boardId: $board->id,
    );

    expect($user->fresh())
        ->last_visited_workspace_id->toBe($board->workspace_id)
        ->last_visited_board_id->toBe($board->id);
});

it('keeps the board id when the same board is visited again', function () {
    // Регрессия: action безусловно обнулял last_visited_board_id, поэтому
    // повторный заход на ту же доску ломал редирект дашборда на доску.
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $user->update([
        'last_visited_workspace_id' => $board->workspace_id,
        'last_visited_board_id' => $board->id,
    ]);

    (new UpdateLastVisitedAction)->execute(
        $user,
        workspaceId: $board->workspace_id,
        boardId: $board->id,
    );

    expect($user->fresh()->last_visited_board_id)->toBe($board->id);
});

it('clears the board id when only a workspace is visited', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $user->update([
        'last_visited_workspace_id' => $board->workspace_id,
        'last_visited_board_id' => $board->id,
    ]);

    (new UpdateLastVisitedAction)->execute(
        $user,
        workspaceId: $board->workspace_id,
    );

    expect($user->fresh())
        ->last_visited_board_id->toBeNull()
        ->last_visited_workspace_id->toBe($board->workspace_id);
});
