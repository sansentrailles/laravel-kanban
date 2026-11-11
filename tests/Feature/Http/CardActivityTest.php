<?php

use App\DTO\Kanban\TextSnapshotDTO;
use App\Jobs\Kanban\PersistCardActivity;
use App\Models\Kanban\Card;
use App\Models\Kanban\CardActivity;
use App\Models\Kanban\Checklist;
use App\Models\Kanban\ChecklistItem;
use App\Models\Kanban\Column;
use App\Models\Kanban\Comment;
use App\Models\Kanban\Label;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

it('queues a card.created activity with the full payload', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $column = Column::factory()->create();
    $column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('boards.cards.store', $column->id), [
            'title' => 'New activity card',
        ])
        ->assertOk();

    $card = Card::query()
        ->where('title', 'New activity card')
        ->firstOrFail();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($card, $column, $user) {
        $property = new ReflectionProperty($job, 'payload');
        $payload = $property->getValue($job);

        return $payload['type'] === 'card.created'
            && $payload['schema_version'] === CardActivity::SCHEMA_VERSION
            && $payload['card_id'] === $card->id
            && $payload['column_id'] === $column->id
            && $payload['board_id'] === $column->board_id
            && $payload['workspace_id'] === $column->board->workspace_id
            && $payload['card_title'] === 'New activity card'
            && $payload['subject_type'] === 'card'
            && $payload['actor'] === ['id' => $user->id, 'name' => $user->name]
            && Str::isUuid($payload['event_id'])
            && $payload['changes'] === []
            && $payload['context']['priority'] === $card->priority?->value;
    });

});

it('stores an activity only once for the same event id', function () {
    DB::connection('mongodb')->getCollection('card_activities')->createIndex(
        ['event_id' => 1],
        ['unique' => true, 'name' => 'event_id_unique']
    );

    $eventId = (string) Str::uuid();

    $payload = [
        'schema_version' => CardActivity::SCHEMA_VERSION,
        'event_id' => $eventId,
        'type' => 'card.created',
        'workspace_id' => 1,
        'board_id' => 1,
        'column_id' => 1,
        'card_id' => 1,
        'card_title' => 'New card',
        'subject_type' => 'card',
        'subject_id' => 1,
        'actor' => ['id' => 1, 'name' => 'Test'],
        'changes' => [],
        'context' => [],
        'created_at' => now(),
    ];

    try {
        (new PersistCardActivity($payload))->handle();
        (new PersistCardActivity($payload))->handle();

        expect(CardActivity::query()->where('event_id', $eventId)->count())->toBe(1);
    } finally {
        CardActivity::query()->where('event_id', $eventId)->delete();
    }
})->group('mongo');

it('records scalar changes as a card.updated activity', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create([
        'title' => 'Old name',
        'description' => 'Old description',
    ]);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), [
            'title' => 'New name',
            'description' => 'New description',
        ])
        ->assertOk();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($card) {
        $property = new ReflectionProperty($job, 'payload');
        $payload = $property->getValue($job);

        return $payload['type'] === 'card.updated'
            && $payload['card_id'] === $card->id
            && $payload['changes']['title'] === [
                'from' => 'Old name',
                'to' => 'New name',
            ]
            && $payload['changes']['description']['from']['length'] === mb_strlen('Old description')
            && $payload['changes']['description']['to']['preview'] === 'New description';
    });
});

it('records label changes as a separate activity', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $workspace = $card->column->board->workspace;
    $workspace->members()->attach($user->id);

    $attached = Label::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Attached']);
    $detached = Label::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Detached']);

    $card->labels()->attach($detached->id);

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.update', $card->id), [
            'label_ids' => [$attached->id],
        ])
        ->assertOk();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($attached, $detached) {
        $property = new ReflectionProperty($job, 'payload');
        $payload = $property->getValue($job);

        return $payload['type'] === 'card.labels_changed'
            && $payload['changes']['added'] === [
                [
                    'id' => $attached->id,
                    'name' => $attached->name,
                    'color' => $attached->color,
                ],
            ]
            && $payload['changes']['removed'] === [
                [
                    'id' => $detached->id,
                    'name' => $detached->name,
                    'color' => $detached->color,
                ],
            ];
    });
});

it('records card movement with from/to context', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create(['ord' => '1000000000.0000000000']);
    $targetColumn = Column::factory()->create(['board_id' => $card->column->board_id]);

    $card->column->board->workspace->members()->attach($user->id);

    $fromColumnId = $card->column_id;
    $fromColumnTitle = $card->column->title;
    $fromOrd = (string) $card->ord;
    $toColumnTitle = $targetColumn->title;

    $this
        ->actingAs($user)
        ->patchJson(route('boards.cards.orders'), [
            'cardId' => $card->id,
            'columnId' => $targetColumn->id,
            'prevCardId' => null,
            'nextCardId' => null,
        ])
        ->assertOk();

    $card->refresh();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($card, $targetColumn, $fromColumnId, $fromColumnTitle, $fromOrd, $toColumnTitle) {
        $property = new ReflectionProperty($job, 'payload');
        $payload = $property->getValue($job);

        return $payload['type'] === 'card.moved'
            && $payload['card_id'] === $card->id
            && $payload['column_id'] === $targetColumn->id
            && $payload['context']['from_column_id'] === $fromColumnId
            && $payload['context']['from_column_title'] === $fromColumnTitle
            && $payload['context']['to_column_title'] === $toColumnTitle
            && $payload['context']['from_ord'] === $fromOrd
            && $payload['context']['to_ord'] === (string) $card->ord
            && $payload['context']['prev_card_id'] === null
            && $payload['context']['next_card_id'] === null;
    });
});

it('records a comment.created activity with a text snapshot', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $content = str_repeat('a', 200);

    $this
        ->actingAs($user)
        ->postJson(route('cards.comments.store', $card->id), [
            'content' => $content,
        ])
        ->assertOk();

    $comment = $card->comments()->firstOrFail();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($comment) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'comment.created'
            && $payload['subject_type'] === 'comment'
            && $payload['subject_id'] === $comment->id
            && $payload['changes']['content'] === TextSnapshotDTO::from($comment->content)->toArray();
    });
});

it('records a comment.updated activity with a before/after snapshot', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $oldContent = str_repeat('a', 200);

    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => $oldContent,
    ]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', ['card' => $card->id, 'comment' => $comment->id]), [
            'content' => str_repeat('b', 200),
        ])
        ->assertOk();

    $comment->refresh();
    expect($comment->is_edited)->toBeTrue()
        ->and($comment->content)->toBe(str_repeat('b', 200));

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($comment, $oldContent) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'comment.updated'
            && $payload['subject_type'] === 'comment'
            && $payload['subject_id'] === $comment->id
            && $payload['changes']['content']['from'] === TextSnapshotDTO::from($oldContent)->toArray()
            && $payload['changes']['content']['to'] === TextSnapshotDTO::from($comment->content)->toArray()
            && $payload['changes']['content']['to']['length'] === 200;
    });
});

it('does not record activity when comment text is unchanged', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => 'Comment',
    ]);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.comments.edit', ['card' => $card->id, 'comment' => $comment->id]), [
            'content' => 'Comment',
        ])
        ->assertOk();

    Queue::assertNotPushed(PersistCardActivity::class);

    expect($comment->refresh()->is_edited)->toBeFalse();
});

it('records a comment.deleted activity with the text snapshot', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $content = str_repeat('a', 200);

    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $user->id,
        'content' => $content,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.comments.delete', ['card' => $card->id, 'comment' => $comment->id]))
        ->assertOk();

    $this->assertSoftDeleted('kanban_comments', ['id' => $comment->id]);

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($comment, $content) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'comment.deleted'
            && $payload['subject_type'] === 'comment'
            && $payload['subject_id'] === $comment->id
            && $payload['changes']['content'] === TextSnapshotDTO::from($content)->toArray();
    });
});

it('does not record activity when a non-author tries a comment', function () {
    /** @var TestCase $this */
    Queue::fake();

    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach([$author->id, $intruder->id]);

    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $author->id,
        'content' => 'Authors comment',
    ]);

    $this
        ->actingAs($intruder)
        ->patchJson(route('cards.comments.edit', ['card' => $card->id, 'comment' => $comment->id]), [
            'content' => 'Edit comment by intruder',
        ])
        ->assertForbidden();

    Queue::assertNotPushed(PersistCardActivity::class);
});

it('does not recrod activity when a non-author tries to delete a comment', function () {
    /** @var TestCase $this */
    Queue::fake();

    $author = User::factory()->create();
    $intruder = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach([$author->id, $intruder->id]);

    $comment = Comment::factory()->create([
        'card_id' => $card->id,
        'user_id' => $author->id,
        'content' => 'Authors comment',
    ]);

    $this
        ->actingAs($intruder)
        ->deleteJson(route('cards.comments.delete', ['card' => $card->id, 'comment' => $comment->id]))
        ->assertForbidden();

    Queue::assertNotPushed(PersistCardActivity::class);
});

it('records a checklist.created activity with a text snapshot', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.checklist.store', $card->id), [
            'title' => 'New checklist',
        ])
        ->assertOk();

    $checklist = $card->checklists()->firstOrFail();
    expect($checklist->title)->toBe('New checklist');

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($checklist) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'checklist.created'
            && $payload['subject_type'] === 'checklist'
            && $payload['subject_id'] === $checklist->id
            && $payload['changes']['title'] === 'New checklist';
    });
});

it('records a checklist.deleted activity with the checklist title', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $checklist = Checklist::factory()->create([
        'card_id' => $card->id,
        'title' => 'New checklist',
    ]);

    ChecklistItem::factory()->count(3)->create(['checklist_id' => $checklist->id]);

    $checklistId = $checklist->id;

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.checklist.delete', ['checklist' => $checklist->id]))
        ->assertOk();

    $this->assertDatabaseMissing('kanban_checklists', ['id' => $checklistId]);

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($checklistId) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'checklist.deleted'
            && $payload['subject_type'] === 'checklist'
            && $payload['subject_id'] === $checklistId
            && $payload['changes']['title'] === 'New checklist'
            && $payload['context']['items_count'] === 3;
    });
});

it('records a checklist_item.created activity with the item content', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['card_id' => $card->id]);
    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.checklist.items.store', $checklist->id), [
            'content' => 'New checklist item',
        ])
        ->assertOk();

    $checklistItem = $checklist->items()->firstOrFail();
    expect($checklistItem->content)->toBe('New checklist item')
        ->and($checklistItem->is_completed)->toBeFalse();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($checklist, $checklistItem) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'checklist_item.created'
            && $payload['subject_type'] === 'checklist_item'
            && $payload['subject_id'] === $checklistItem->id
            && $payload['context'] === [
                'content' => 'New checklist item',
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ];
    });
});

it('records a checklist_item.toggled activity from false to true', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['card_id' => $card->id]);
    $checklistItem = ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'is_completed' => false, 'content' => 'checklist item']);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.checklists.items.toggle', ['checklist' => $checklist->id, 'item' => $checklistItem->id]))
        ->assertOk();

    $item = $checklistItem->refresh();
    expect($item->is_completed)->toBeTrue();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($checklist, $item) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'checklist_item.toggled'
            && $payload['subject_type'] === 'checklist_item'
            && $payload['subject_id'] === $item->id
            && $payload['changes']['is_completed'] === ['from' => false, 'to' => true]
            && $payload['context'] === [
                'content' => $item->content,
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ];
    });
});

it('records a checklist_item.toggled activity from true to false', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['card_id' => $card->id]);
    $checklistItem = ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'is_completed' => true, 'content' => 'checklist item']);

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->patchJson(route('cards.checklists.items.toggle', ['checklist' => $checklist->id, 'item' => $checklistItem->id]))
        ->assertOk();

    $item = $checklistItem->refresh();
    expect($item->is_completed)->toBeFalse();

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($checklist, $item) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'checklist_item.toggled'
            && $payload['subject_type'] === 'checklist_item'
            && $payload['subject_id'] === $item->id
            && $payload['changes']['is_completed'] === ['from' => true, 'to' => false]
            && $payload['context'] === [
                'content' => $item->content,
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ];
    });
});

it('records a checklist_item.deleted activity with the checklist item content', function () {
    /** @var TestCase $this */
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $checklist = Checklist::factory()->create(['card_id' => $card->id]);
    $checklistItem = ChecklistItem::factory()->create(['checklist_id' => $checklist->id, 'content' => 'Deleted checklist item']);
    $other = ChecklistItem::factory()->create(['checklist_id' => $checklist->id]);
    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.checklists.items.delete', ['checklist' => $checklist->id, 'item' => $checklistItem->id]))
        ->assertOk();

    $this->assertDatabaseMissing('kanban_checklist_items', [
        'id' => $checklistItem->id,
    ]);

    $this->assertDatabaseHas('kanban_checklist_items', [
        'id' => $other->id,
    ]);

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($checklist, $checklistItem) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'checklist_item.deleted'
            && $payload['subject_type'] === 'checklist_item'
            && $payload['subject_id'] === $checklistItem->id
            && $payload['context'] === [
                'content' => $checklistItem->content,
                'checklist_id' => $checklist->id,
                'checklist_title' => $checklist->title,
            ];
    });
});

it('records an attachment.uploaded activity with file metadata', function () {
    /** @var TestCase $this */
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id), [
            'file' => UploadedFile::fake()->image('roadmap.png'),
        ])
        ->assertCreated();

    $attachment = $card->attachments()->firstOrFail();
    Storage::disk('public')->assertExists($attachment->path);

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($attachment) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'attachment.uploaded'
            && $payload['subject_type'] === 'attachment'
            && $payload['subject_id'] === $attachment->id
            && $payload['changes'] === []
            && $payload['context'] === [
                'name' => $attachment->name,
                'mime' => $attachment->mime_type,
                'size' => $attachment->size,
            ];
    });
});

it('records an attachment.deleted activity with file metadata', function () {
    /** @var TestCase $this */
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $path = 'attachments/2026/10/test.pdf';
    Storage::disk('public')->put($path, 'content');
    $attachment = $card->attachments()->create(['user_id' => $user->id, 'name' => 'test.pdf', 'path' => $path, 'mime_type' => 'application/pdf', 'size' => 7]);
    $attachmentId = $attachment->id;
    $meta = [
        'name' => $attachment->name,
        'mime' => $attachment->mime_type,
        'size' => $attachment->size,
    ];

    Storage::disk('public')->assertExists($path);
    $this->assertDatabaseHas('kanban_attachments', ['id' => $attachment->id]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.attachments.destroy', ['card' => $card->id, 'attachment' => $attachment->id]))
        ->assertOk()
        ->assertJsonPath('attachments_count', 0)
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('kanban_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($path);

    Queue::assertPushed(PersistCardActivity::class, function (PersistCardActivity $job) use ($attachmentId, $meta) {
        $payload = (new ReflectionProperty($job, 'payload'))->getValue($job);

        return $payload['type'] === 'attachment.deleted'
            && $payload['subject_type'] === 'attachment'
            && $payload['subject_id'] === $attachmentId
            && $payload['changes'] === []
            && $payload['context'] === $meta;

    });
});

it('does not record an attachment.deleted activity for other card', function () {
    /** @var TestCase $this */
    Queue::fake();
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $otherCard = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $path = 'attachments/2026/10/test.pdf';
    Storage::disk('public')->put($path, 'content');
    $attachment = $card->attachments()->create(['user_id' => $user->id, 'name' => 'test.pdf', 'path' => $path, 'mime_type' => 'application/pdf', 'size' => 7]);

    Storage::disk('public')->assertExists($path);
    $this->assertDatabaseHas('kanban_attachments', ['id' => $attachment->id]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.attachments.destroy', ['card' => $otherCard->id, 'attachment' => $attachment->id]))
        ->assertNotFound();

    $this->assertDatabaseHas('kanban_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertExists($attachment->path);

    Queue::assertNotPushed(PersistCardActivity::class);
});
