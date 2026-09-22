<?php
// App\Http\Resources\Kanban\CardResource.php

declare(strict_types=1);

namespace App\Http\Resources\Kanban;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'column_id' => $this->column_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority?->value,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'order' => (string) $this->order, // Строка для точности decimal в JS
            
            // Связи
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            
            // Агрегации
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),
            
            // Форматируем чек-листы в удобный для Vue формат { total: X, completed: Y }
            'checklist' => $this->whenLoaded('checklists', function () {
                $total = 0;
                $completed = 0;
                
                foreach ($this->checklists as $checklist) {
                    $total += $checklist->items()->count();
                    $completed += $checklist->items()->where('is_completed', true)->count();
                }
                
                return $total > 0 ? ['total' => $total, 'completed' => $completed] : null;
            }),
            
            'is_overdue' => $this->isOverdue(),
        ];
    }
}