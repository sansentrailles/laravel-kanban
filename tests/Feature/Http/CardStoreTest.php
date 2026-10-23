<?php

use App\Events\Kanban\CardCreated;
use App\Models\Kanban\Column;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

it('allows a workspace member to create a card in the column', function () {
    Queue::fake();

    $user = User::factory()->create();
    $column = Column::factory()->create();
    $column->board->workspace->members()->attach($user->id);

    $response = $this
        ->actingAs($user)
        ->postJson(route('boards.cards.store', $column->id), [
            'title' => 'Новая карточка',
        ])
        ->assertOk();

    // Карточка создана в БД и возвращена в ответе
    $this->assertDatabaseHas('kanban_cards', [
        'column_id' => $column->id,
        'title' => 'Новая карточка',
    ]);
    expect($response->json('card.title'))->toBe('Новая карточка');
});

it('forbids creating a card for a user outside the workspace', function () {
    $user = User::factory()->create();
    $column = Column::factory()->create();

    $this
        ->actingAs($user)
        ->postJson(route('boards.cards.store', $column->id), [
            'title' => 'Взлом',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('kanban_cards', ['title' => 'Взлом']);
});

it('broadcasts CardCreated with the full card contract for remote clients', function () {
    // Регрессия на payload без связей: фронт (Store) пушит новую карточку
    // целиком, а Card.vue читает card.labels.length напрямую. Когда ключей
    // связей нет (whenLoaded опустил их), рендер падал на undefined.length
    // и удалённые клиенты не видели новую карточку.
    Queue::fake();

    $user = User::factory()->create();
    $column = Column::factory()->create();
    $column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('boards.cards.store', $column->id), [
            'title' => 'Контракт карточки',
        ])
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) {
        if (! $job->event instanceof CardCreated) {
            return false;
        }

        $card = $job->event->broadcastWith()['card'];

        return array_key_exists('labels', $card)
            && array_key_exists('assignees', $card)
            && array_key_exists('checklists', $card)
            && array_key_exists('comments', $card)
            // Вложенные Resource-коллекции при resolve() остаются объектами:
            // json-энкодинг в реальной отправке превратит их в [], здесь
            // проверяем их так же — через собственный resolve()
            && $card['labels']->resolve() === [];
    });
});
