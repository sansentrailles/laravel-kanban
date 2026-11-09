<?php

declare(strict_types=1);

namespace App\Http\Resources\Kanban;

use App\Enums\Kanban\CardActivityType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = is_array($this->actor) ? $this->actor : [];

        return [
            'id' => (string) $this->_id,
            'type' => $this->type,
            'type_label' => CardActivityType::tryFrom($this->type)?->label(),
            'actor' => [
                'id' => $actor['id'] ?? null,
                'name' => $actor['name'] ?? '',
            ],
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'changes' => $this->changes ?? [],
            'context' => $this->context ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
