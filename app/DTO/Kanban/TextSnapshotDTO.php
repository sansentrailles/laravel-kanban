<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class TextSnapshotDTO implements Arrayable
{
    public const PREVIEW_LIMIT = 120;

    public function __construct(
        public int $length,
        public string $preview
    ) {}

    public static function from(?string $text): ?self
    {
        if ($text === null) {
            return null;
        }

        return new self(
            length: mb_strlen($text),
            preview: Str::limit($text, self::PREVIEW_LIMIT, '…')
        );
    }

    public function toArray(): array
    {
        return [
            'length' => $this->length,
            'preview' => $this->preview,
        ];
    }
}