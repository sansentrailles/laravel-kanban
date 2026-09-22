<?php

namespace App\Http\Resources\Kanban;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColumnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'title' => $this->title,
            'color' => $this->color,
            'order' => $this->order,
            'is_hidden' => $this->is_hidden,
            'wip_limit' => $this->wip_limit,
            'settings' => $this->settings ?? [],

            // Карточки внутри колонки
            'cards' => CardResource::collection($this->whenLoaded('cards')),
        ];
    }
}
