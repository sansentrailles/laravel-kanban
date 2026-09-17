<?php

namespace App\Http\Resources\Kanban;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Kanban\LabelResource;

class CardResource extends JsonResource
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
            'column_id' => $this->column_id,
            'title' => $this->title,
            'description' => $this->description,

            // Преобразование enum в строку для Вью
            'priority' => $this->description?->value,

            'due_date' => $this->when($this->due_date, fn () => $this->due_date->toDateString()),
            'start_date' => $this->when($this->start_date, fn () => $this->start_date->toDateString()),
            'completed_at' => $this->when($this->completed_at, fn () => $this->completed_at->toDateString()),

            'order' => (string) $this->order, // Строка, чтобы избежать проблем с точностью float/decimal в JS

            // Связи
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'assagnee' => UserResource::collection($this->whenLoaded('assgnee')),

            // Агрегации
            // TODO: реализовать
            // 'comments_count' => $this->whenCounted('comments'),
            // 'attachments_count' => $this->whenCounted('attachments'),

            // Вычисляемое поле для чек-листа
            // 'checklist' => $this->w

            'is_overdue' => $this->isOverdue(),
        ];
    }
}
