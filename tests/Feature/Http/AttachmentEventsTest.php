<?php

use App\Events\Kanban\AttachmentDeleted;
use App\Events\Kanban\AttachmentUploaded;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

it('broadcasts AttachmentUploaded with the attachment and a fresh counter', function () {
    /** @var TestCase $this */
    Storage::fake('public');
    Queue::fake();

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

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card, $attachment) {
        if (! $job->event instanceof AttachmentUploaded) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['attachment']['id'] === $attachment->id
            && $payload['attachment']['name'] === 'roadmap.png'
            && $payload['attachments_count'] === 1;
    });
});

it('broadcasts AttachmentDeleted with the id and a fresh counter', function () {
    /** @var TestCase $this */
    Storage::fake('public');
    Queue::fake();

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $card->column->board->workspace->members()->attach($user->id);

    $path = 'attachments/2026/10/test.pdf';
    Storage::disk('public')->put($path, 'content');
    $attachment = $card->attachments()->create([
        'user_id' => $user->id,
        'name' => 'test.pdf',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 7,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.attachments.destroy', [
            'card' => $card->id,
            'attachment' => $attachment->id,
        ]))
        ->assertOk();

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($card, $attachment) {
        if (! $job->event instanceof AttachmentDeleted) {
            return false;
        }

        $payload = $job->event->broadcastWith();

        return $payload['card_id'] === $card->id
            && $payload['attachment_id'] === $attachment->id
            && $payload['attachments_count'] === 0;
    });
});
