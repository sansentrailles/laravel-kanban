<?php

namespace App\Http\Resources\Kanban;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
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
            'name' => $this->name,
            'plan' => 1,
            'slug' => $this->slug,
            'description' => $this->description,
            'owner_id' => $this->owner_id,

            // Отношения - загружаются только при жадной загрузке
            'owner' => new UserResource($this->whenLoaded('owner')),
            'members_count' => $this->whenCounted('members'),

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
