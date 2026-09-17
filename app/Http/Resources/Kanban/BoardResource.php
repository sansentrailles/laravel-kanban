<?php

namespace App\Http\Resources\Kanban;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Kanban\ColumnResource;

class BoardResource extends JsonResource
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
            'uuid' => $this->uuid,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'visibility' => $this->visibility,
            'settings' => $this->settings ?? [],

            // Отношения
            'columns' => ColumnResource::collection($this->whenLoaded('columns')),

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
