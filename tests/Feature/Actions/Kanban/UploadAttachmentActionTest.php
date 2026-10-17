<?php

use App\Actions\Kanban\UploadAttachmentAction;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores the file and records an attachment', function () {
    Storage::fake('public');

    $card = Card::factory()->create();
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('roadmap.png');

    $attachment = (new UploadAttachmentAction)->execute($card, $file, $user);

    Storage::disk('public')->assertExists($attachment->path);

    expect($attachment)
        ->card_id->toBe($card->id)
        ->user_id->toBe($user->id)
        ->name->toBe('roadmap.png')
        ->path->toStartWith('attachments/')
        ->mime_type->toBe('image/png')
        ->size->toBeGreaterThan(0);
});
