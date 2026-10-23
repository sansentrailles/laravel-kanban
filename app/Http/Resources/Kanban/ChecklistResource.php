<?php

namespace App\Http\Resources\Kanban;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
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
            'title' => $this->title,
            // resolve() сразу, чтобы вложенная коллекция в payload события
            // (и в тестах) была обычным массивом, как в CardResource
            'items' => $this->whenLoaded('items', fn () => ChecklistItemResource::collection($this->items)->resolve()),
        ];
    }
}
