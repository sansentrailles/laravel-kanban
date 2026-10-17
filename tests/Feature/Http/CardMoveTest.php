<?php

use App\Events\Kanban\CardMoved;
use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

it('moves a card between columns and queues a broadcast so other clients can sync', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create(['ord' => '1000000000.0000000000']);
    $targetColumn = Column::factory()->create(['board_id' => $card->column->board_id]);

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

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card) {
        return $job->event instanceof CardMoved
            && $job->event->card->id === $card->id;
    });
});
