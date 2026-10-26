<?php

use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

it('allows a workspace member to upload a file to a card', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $file = UploadedFile::fake()->image('test.png');

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id), [
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('attachments_count', 1)
        ->assertJsonPath('attachment.name', 'test.png');

    // Запись в БД
    $this->assertDatabaseHas('kanban_attachments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
        'name' => 'test.png',
        'mime_type' => 'image/png',
    ]);

    $attachment = $card->attachments()->firstOrFail();

    expect($attachment->path)->toStartWith('attachments/');
    Storage::disk('public')->assertExists($attachment->path);
});

it('forbids uploading a file for a user outside the workspace', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $file = UploadedFile::fake()->image('test.png');

    $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id), [
            'file' => $file,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('kanban_attachments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('requires a file to upload', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    $this->assertDatabaseMissing('kanban_attachments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects files with a disallowed extension', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $file = UploadedFile::fake()->create('application.exe', 10, 'application/x-msdownload');

    $card->column->board->workspace->members()->attach($user->id);

    $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id), [
            'file' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    $this->assertDatabaseMissing('kanban_attachments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects files larger than 10 MB', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $file = UploadedFile::fake()->create('archive.zip', 10241);

    $card->column->board->workspace->members()->attach($user->id);

    $response = $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id), [
            'file' => $file,
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'file' => 'Размер файла не должен превышать 10 МБ.',
        ]);

    $this->assertDatabaseMissing('kanban_attachments', [
        'card_id' => $card->id,
        'user_id' => $user->id,
    ]);

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('deletes the attachment record and the stored file for a workspace member', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $path = 'attachments/2026/10/test.pdf';
    Storage::disk('public')->put($path, 'content');
    $attachment = $card->attachments()->create(['user_id' => $user->id, 'name' => 'test.pdf', 'path' => $path, 'mime_type' => 'application/pdf', 'size' => 7]);

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
});

it('deletes the attachment record when the stored file is already missing', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();

    $card->column->board->workspace->members()->attach($user->id);

    $attachment = $card->attachments()->create([
        'user_id' => $user->id,
        'name' => 'missing.pdf',
        'path' => 'attachments/2026/10/missing.pdf',
        'mime_type' => 'application/pdf',
        'size' => 7,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.attachments.destroy', [
            'card' => $card->id,
            'attachment' => $attachment->id,
        ]))
        ->assertOk()
        ->assertJsonPath('attachments_count', 0);

    $this->assertDatabaseMissing('kanban_attachments', ['id' => $attachment->id]);
});

it('forbids deleting an attachment for a user outside the workspace', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();

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
        ->deleteJson(route('cards.attachments.destroy', ['card' => $card->id, 'attachment' => $attachment->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('kanban_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertExists($path);
});

it('returns 404 when the attachment belongs to another card', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create();
    $cardA = Card::factory()->create();
    $cardB = Card::factory()->create(['column_id' => $cardA->column_id]);

    $cardA->column->board->workspace->members()->attach($user->id);

    $path = 'attachments/2026/10/foreign.pdf';
    Storage::disk('public')->put($path, 'content');
    $attachment = $cardB->attachments()->create([
        'user_id' => $user->id,
        'name' => 'foreign.pdf',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 7,
    ]);

    $this
        ->actingAs($user)
        ->deleteJson(route('cards.attachments.destroy', ['card' => $cardA->id, 'attachment' => $attachment->id]))
        ->assertNotFound();

    $this->assertDatabaseHas('kanban_attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertExists($path);
});
