<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

use Illuminate\Http\UploadedFile;

readonly class AttachmentDTO
{
    public function __construct(
        public int $cardId,
        public int $userId,
        public UploadedFile $file
    ) {}
}
