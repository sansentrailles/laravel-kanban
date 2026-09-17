<?php

declare(strict_types=1);

namespace App\Actions\Kanban;

use App\DTO\Kanban\AttachmentDTO;
use App\Models\Kanban\Attachment;
use Illuminate\Support\Str;

final class UploadAttachmentAction
{
    public function execute(AttachmentDTO $dto): Attachment
    {
        // Генерирация безопасного имени, чтобы избежать коллизий
        $filename = Str::uuid() . '.' . $dto->file->getClientOriginalExtension();

        // Путь внутри хранилища
        $directory = 'attachments/' . date('Y/m');

        // Сохранение файла
        $path = $dto->file->storeAs($directory, $filename, 'public');

        return Attachment::create([
            'card_id' => $dto->cardId,
            'user_id' => $dto->userId,
            'name' => $dto->file->getClientOriginalName(),
            'mime_type' => $dto->file->getMimeType(),
            'size' => $dto->file->getSize(),
        ]);
    }
}