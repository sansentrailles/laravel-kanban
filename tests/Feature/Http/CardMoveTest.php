<?php

use App\Events\Kanban\CardMoved;
use App\Models\Kanban\Board;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

it('does not move a card into a column that is at its WIP limit', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create(); // другой столбец без лимита
    $limited = Column::factory()->create([
        'board_id' => $card->column->board_id,
        'wip_limit' => 1,
    ]);

    $card->column->board->workspace->members()->attach($user->id);

    Card::factory()->create(['column_id' => $limited->id]);

    $this->actingAs($user)
        ->withHeaders(['Accept' => 'application/json']) // как реальный axios
        ->patch(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $limited->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('wip_limit');

    expect($card->refresh()->column_id)->not->toBe($limited->id);
});

it('moves a card between columns and queues a broadcast so other clients can sync', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create(['ord' => '1000000000.0000000000']);
    $targetColumn = Column::factory()->create(['board_id' => $card->column->board_id]);

    $card->column->board->workspace->members()->attach($user->id);

    $this->actingAs($user)
        ->patch(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $targetColumn->id,
            'prevCardId' => null,
            'nextCardId' => null,
        ])
        ->assertOk();

    expect($card->refresh())
        ->column_id->toBe($targetColumn->id)
        ->and(bccomp((string) $card->ord, '1000000000.0000000000', 10))->toBe(0);

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card, $targetColumn) {
        if (! $job->event instanceof CardMoved || $job->event->card->id !== $card->id) {
            return false;
        }

        // Регрессия: ресурс отдавал порядок под несуществующим ключом 'order',
        // а фронт (moveCardRemote) читает updatedCard.ord. Из-за NaN при
        // сравнении удалённый клиент вставлял карточку всегда первой и не
        // менял порядок при переупорядочивании внутри колонки.
        $payloadCard = $job->event->broadcastWith()['card'];

        return ($payloadCard['ord'] ?? null) === (string) $card->ord
            && ($payloadCard['column_id'] ?? null) === $targetColumn->id;
    });
});

it('forbids moving a card for a user outside workspace', function () {
    /** @var TestCase $this */

    // -- Arrange --
    $user = User::factory()->create();     // юзер - НЕ owner, НЕ member
    $card = Card::factory()->create();     // у воркспейса фабрика автоматически  сделает owner'ом ДРУГОГО юзера
    $target = Column::factory()->create(['board_id' => $card->column->board_id]); // целевая колонка в том же воркспейсе

    // нет attach - поэтому тест называется "outside thw workspace"

    // -- Act - чужак просит перенести --
    $response = $this->actingAs($user)
        ->patch(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $target->id,
        ]);

    // -- Assert: запрос отвергнут и ничего не изменилось
    $response->assertForbidden();

    // Изоляция: картоку в столбце не сдвинули
    expect($card->refresh()->column_id)->toBe($card->column_id)
        ->and($target->refresh()->cards()->count())->toBe(0);
});

it('forbids moving a card into a column of another workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $foreignColumn = Column::factory()->create(); // своя доска и свой воркспейс

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patch(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $foreignColumn->id,
        ])
        ->assertForbidden();

    expect($card->refresh()->column_id)->not->toBe($foreignColumn->id)
        ->and($foreignColumn->cards()->count())->toBe(0);
});

it('forbids moving a card to a column of another board in the same workspace', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $workspace = $card->column->board->workspace;

    $otherBoard = Board::factory()->create(['workspace_id' => $workspace->id]);
    $otherColumn = Column::factory()->create(['board_id' => $otherBoard->id]);

    $workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patch(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $otherColumn->id,
        ])
        ->assertForbidden();

    expect($card->refresh()->column_id)->not->toBe($otherColumn->id)
        ->and($otherColumn->cards()->count())->toBe(0);
});

it('rejects a neighbour card from another column', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $targetColumn = Column::factory()->create(['board_id' => $card->column->board_id]);
    $foreignNeighbour = Card::factory()->create(); // другая колонка другой доски

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $targetColumn->id,
            'prevCardId' => $foreignNeighbour->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('prevCardId');

    expect($card->refresh()->column_id)->not->toBe($targetColumn->id);
});

it('moves a card between neighbours in the target column', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $card = Card::factory()->create();
    $targetColumn = Column::factory()->create([
        'board_id' => $card->column->board_id,
        'wip_limit' => null,
    ]);

    $prevCard = Card::factory()->create([
        'column_id' => $targetColumn->id,
        'ord' => '1000000000.0000000000',
    ]);
    $nextCard = Card::factory()->create([
        'column_id' => $targetColumn->id,
        'ord' => '2000000000.0000000000',
    ]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patch(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $targetColumn->id,
            'prevCardId' => $prevCard->id,
            'nextCardId' => $nextCard->id,
        ])
        ->assertOk();

    expect($card->refresh())
        ->column_id->toBe($targetColumn->id)
        ->and(bccomp((string) $card->ord, (string) $prevCard->ord, 10))->toBe(1)
        ->and(bccomp((string) $card->ord, (string) $nextCard->ord, 10))->toBe(-1);
});
