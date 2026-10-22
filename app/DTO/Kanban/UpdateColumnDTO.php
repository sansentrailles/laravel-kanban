<?php

declare(strict_types=1);

namespace App\DTO\Kanban;

use App\Http\Requests\Kanban\UpdateColumnRequest;

readonly class UpdateColumnDTO
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
    public static function fromRequest(UpdateColumnRequest $request): self
    {
        // safe()->only вернет только поля, прошедшие валидацию
        return new self(
            $request->safe()->only(['color', 'title', 'wip_limit'])
        );
    }
}
