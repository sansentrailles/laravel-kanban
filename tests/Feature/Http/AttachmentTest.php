<?php

use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

it('allows a workspace member to upload a file to a card', function () {
    /** @var TestCase $this */
    Storage::fake('avatars');

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

});

it('forbids uploading file for a user outside the workspace', function () {
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
        ->assertJsonValidationErrors('file');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects files larger than 10 MB', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $card = Card::factory()->create();
    $file = UploadedFile::fake()->create('archive.zip', 10241 * 2);

    $card->column->board->workspace->members()->attach($user->id);

    $response = $this
        ->actingAs($user)
        ->postJson(route('cards.attachments.store', $card->id), [
            'file' => $file,
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'file' => 'Размер файла не должен превышать 10 МБ.',
        ]);

    Storage::disk('public')->assertMissing($file->hashName());
    expect(Storage::disk('public')->allFiles())->toBe([]);
});
