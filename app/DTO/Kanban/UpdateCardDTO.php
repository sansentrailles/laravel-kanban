<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

use App\Http\Requests\Kanban\CardUpdateRequest;

readonly class UpdateCardDTO
{
    /**
     * @param  array<string, mixed>  $attributes  Массив только тех полей, которые пришли в запросе
     */
    public function __construct(
        public array $attributes = []
    ) {}

    /**
     * Фабричный метод для создания DTO из валидированного запроса
     */
    public static function fromRequest(CardUpdateRequest $request): self
    {
        // safe()->only вернет только те поля, которые были переданы в запросе и прошли валидацию
        // Например, если пришел только title, вернется ['title' => 'Новое название']
        return new self(
            $request->safe()->only(['title', 'description', 'start_date', 'due_date'])
        );
    }
}
