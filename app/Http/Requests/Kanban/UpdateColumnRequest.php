<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateColumnRequest extends FormRequest
{
    /**
     * Права на обновление колонки проверяет ColumnPolicy::update():
     * участник воркспейса, к которому принадлежит колонка.
     */
    public function authorize(): bool
    {
        $column = $this->route('column');

        return $this->user()->can('update', $column);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Пока обновлять можно только цвет; при добавлении правки
            // названия / WIP-лимита правила расширятся здесь же
            'color' => [
                'nullable',
                'string',
                // Формат HEX #RRGGBB — как и при создании колонки
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'color.regex' => 'Цвет должен быть в формате HEX (например, #4b82f6)',
        ];
    }
}
