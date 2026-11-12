<?php

declare(strict_types=1);

use App\Models\Kanban\Board;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\Kanban\Workspace;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

it('renders only archived cards of the board', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user->id);

    $board = Board::factory()->create(['workspace_id' => $workspace->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);

    $archived = Card::factory()->create([
        'column_id' => $column->id,
        'title' => 'Archived',
    ]);
    $archived->delete();

    // Карточка, которая не попадает в архив
    Card::factory()->create(['column_id' => $column->id]);

    // Архив другой доски того же воркспейса
    $foreignBoard = Board::factory()->create(['workspace_id' => $workspace->id]);
    $foreignColumn = Column::factory()->create(['board_id' => $foreignBoard->id]);
    Card::factory()->create(['column_id' => $foreignColumn->id])->delete();

    $this->actingAs($user)
        ->get(route('kanban.board.archive', [
            'workspace' => $workspace->slug,
            'board' => $board->uuid,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Boards/Archive')
            ->where('board.uuid', $board->uuid)
            ->has('archivedCards.data', 1)
            ->where('archivedCards.data.0.id', $archived->id)
            ->where('archivedCards.data.0.title', 'Archived')
            ->where('archivedCards.data.0.column_title', $column->title)
            ->where('archivedCards.meta.total', 1)
        );
});

it('forbids a user outside the workspace', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $this->actingAs($user)
        ->get(route('kanban.board.archive', [
            'workspace' => $board->workspace->slug,
            'board' => $board->uuid,
        ]))
        ->assertForbidden();
});

it('returns 404 when the board does not belong to the workspace in the url', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user->id);

    $foreignBoard = Board::factory()->create();
    $foreignBoard->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->get(route('kanban.board.archive', [
            'workspace' => $workspace->slug,
            'board' => $foreignBoard->uuid,
        ]))
        ->assertNotFound();
});

it('requires authentication', function () {
    /** @var TestCase $this */
    $board = Board::factory()->create();

    $this->get(route('kanban.board.archive', [
        'workspace' => $board->workspace->slug,
        'board' => $board->uuid,
    ]))->assertRedirect(route('login'));
});
