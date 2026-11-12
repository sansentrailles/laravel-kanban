<?php

declare(strict_types=1);

namespace App\Http\Resources\Kanban;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchivedCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'column_id' => $this->column_id,
            'column_title' => $this->column?->title,
            'priority' => $this->priority?->value,
            'archived_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
