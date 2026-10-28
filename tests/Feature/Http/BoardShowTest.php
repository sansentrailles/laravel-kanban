<?php

use App\Models\Kanban\Board;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\Kanban\Workspace;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

it('renders the board page with a self-contained card payload', function () {
    /** @var TestCase $this */

    // Регрессия: карточки на доске приходили без ключа comments (whenLoaded
    // опускал связь) — стор падал на card.comments.push при добавлении
    // комментария; без checklists_count пропадал счётчик на карточке.
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user->id);

    $board = Board::factory()->create(['workspace_id' => $workspace->id]);
    $column = Column::factory()->create(['board_id' => $board->id]);
    $card = Card::factory()->create(['column_id' => $column->id]);

    $card->comments()->create([
        'user_id' => $user->id,
        'content' => 'Первый комментарий',
    ]);

    $this->actingAs($user)
        ->get(route('kanban.board', [
            'workspace' => $workspace->slug,
            'board' => $board->uuid,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Boards/Show')
            ->has('workspaces')
            ->has('boards.0.uuid')
            ->missing('boards.data')
            ->has('currentWorkspace.labels')
            // AssigneePicker в модалке карточки читает members.data; без
            // загруженной связи список участников был пустым.
            ->has('currentWorkspace.members.data', 1)
            ->where('currentWorkspace.members.data.0.id', $user->id)
            // Лейблы уже приходят в currentWorkspace.labels, отдельный проп не нужен.
            ->missing('workspaceLabels')
            ->has('board.columns.data.0.cards.0', fn (Assert $card) => $card
                ->has('comments', 1)
                ->has('comments.0.content')
                ->has('comments.0.user.name')
                ->has('checklists_count')
                ->has('comments_count')
                ->has('labels')
                ->has('assignees')
                ->has('checklists')
                ->etc()
            )
        );
});

it('forbids viewing a board of another workspace', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $this->actingAs($user)
        ->get(route('kanban.board', [
            'workspace' => $board->workspace->slug,
            'board' => $board->uuid,
        ]))
        ->assertForbidden();
});

it('returns 404 when the board does not belong to the workspace in the url', function () {
    // Регрессия: доска должна резолвиться только внутри воркспейса из URL,
    // иначе через slug чужого воркспейса можно было открыть свою доску
    // и увидеть участников и метки того воркспейса.
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user->id);

    $foreignBoard = Board::factory()->create();
    $foreignBoard->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->get(route('kanban.board', [
            'workspace' => $workspace->slug,
            'board' => $foreignBoard->uuid,
        ]))
        ->assertNotFound();
});
