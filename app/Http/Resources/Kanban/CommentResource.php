<?php

declare(strict_types=1);

namespace App\Http\Resources\Kanban;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CommentResource extends JsonResource
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
            'content' => $this->content,
            'is_edited' => (bool) $this->is_edited,

            // Форматируем даты в ISO 8601 для удобного парсинга на фронтенде (Vue/Day.js)
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Безопасная обработка связи с пользователем
            'user' => $this->whenLoaded('user', function () {
                // Если пользователь был удален (nullOnDelete сработал)
                if (! $this->user) {
                    return [
                        'id' => null,
                        'name' => 'Удаленный пользователь',
                        'email' => null,
                        'avatar_url' => null,
                    ];
                }

                // Если пользователь существует
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar_url' => $this->user->avatar_url ?? null,
                ];
            }),
        ];
    }
}
