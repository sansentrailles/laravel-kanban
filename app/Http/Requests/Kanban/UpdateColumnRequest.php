<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $column = $this->route('column');

        return [
            // Частичное обновление: присылаем только поле, которое меняем
            'color' => [
                'nullable',
                'string',
                // Формат HEX #RRGGBB — как и при создании колонки
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'title' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
                Rule::unique('kanban_columns', 'title')
                    // Название должно быть уникальным в пределах ДОСКИ колонки
                    ->where('board_id', $column->board_id)
                    // ...а своё текущее название (без изменений) считаем валидным
                    ->ignore($column->id),
            ],
            'wip_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'color.regex' => 'Цвет должен быть в формате HEX (например, #4b82f6)',
            'title.unique' => 'Колонка с таким названием уже существует в этой доске',
            'title.min' => 'Название колонки не может быть пустым',
        ];
    }
}
