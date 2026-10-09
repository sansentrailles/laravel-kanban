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
            'testField' => 'TEST FIELD',

            // Связи
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'checklists' => ChecklistResource::collection($this->whenLoaded('checklists')),

            // 'checklists' => $this->whenLoaded('checklists', function () {
            //     return $this->checklists->map(function ($checklist) {
            //         return [
            //             'id' => $checklist->id,
            //             'title' => $checklist->title,
            //             'items' => $checklist->items->map(function ($item) {
            //                 return [
            //                     'id' => $item->id,
            //                     'content' => $item->content,
            //                     'completed' => (bool) $item->is_completed,
            //                 ];
            //             })->values()->all(), // <--- Добавлено values()->all()
            //         ];
            //     })->values()->all(); // <--- Добавлено values()->all()
            // }),

            // Агрегации
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),

            // Форматируем чек-листы в удобный для Vue формат { total: X, completed: Y }
            // 'checklist' => $this->whenLoaded('checklists', function () {
            //     $total = 0;
            //     $completed = 0;

            //     foreach ($this->checklists as $checklist) {
            //         // Используем коллекцию items, которая уже загружена, а не items()->count()
            //         $total += $checklist->items->count();
            //         $completed += $checklist->items->where('is_completed', true)->count();
            //     }

            //     return $total > 0 ? ['total' => $total, 'completed' => $completed] : null;
            // }),

            'is_overdue' => $this->isOverdue(),
        ];
    }
}
