<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\Models\Kanban\Attachment;
use App\Models\Kanban\Card;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class UploadAttachmentAction
{
    public function execute(Card $card, UploadedFile $file, User $user): Attachment
    {
        // Генерирация безопасного имени, чтобы избежать коллизий
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        // Путь внутри хранилища
        $directory = 'attachments/'.date('Y/m');

        // Сохранение файла
        $path = $file->storeAs($directory, $filename, 'public');

        return Attachment::create([
            'card_id' => $card->id,
            'user_id' => $user->id,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
