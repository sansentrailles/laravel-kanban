<?php

use App\Events\Kanban\ChecklistUpdated;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

function checklistCardFor(User $user): Card
{
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    return $card;
}

it('broadcasts ChecklistUpdated when a checklist is created', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = checklistCardFor($user);

    $this
        ->actingAs($user)
        ->postJson(route('cards.checklist.store', $card->id), [
            'title' => 'Definition of Done',
        ])
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card) {
        if (! $job->event instanceof ChecklistUpdated) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['checklists_count'] === 1
            && $payload['checklists'][0]['title'] === 'Definition of Done'
            && $payload['checklists'][0]['items'] === [];
    });
});

it('broadcasts ChecklistUpdated with item state after toggling', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = checklistCardFor($user);
    $checklist = $card->checklists()->create(['title' => 'Пункты']);
    $item = $checklist->items()->create([
        'content' => 'Первый пункт',
        'is_completed' => false,
    ]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.checklists.items.toggle', [
            'checklist' => $checklist->id,
            'item' => $item->id,
        ]))
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card, $item) {
        if (! $job->event instanceof ChecklistUpdated) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['checklists'][0]['items'][0]['id'] === $item->id
            && $payload['checklists'][0]['items'][0]['completed'] === true;
    });
});

it('broadcasts ChecklistUpdated when a checklist is deleted', function () {
    Queue::fake();

    $user = User::factory()->create();
    $card = checklistCardFor($user);
    $checklist = $card->checklists()->create(['title' => 'Удаляемый чек-лист']);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.checklist.delete', $checklist->id))
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card) {
        if (! $job->event instanceof ChecklistUpdated) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['checklists_count'] === 0
            && $payload['checklists'] === [];
    });
});
