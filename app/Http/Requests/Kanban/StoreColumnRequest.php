<?php

declare(strict_types=1);

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Column;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('board'));
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Column::getTable(), 'title')
                    ->where('board_id', $this->route('board')->id),
            ],
            'color' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
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
            'title.uniqie' => 'Колонка с таким названием уже существует в этой доске',
            'color.regex' => 'Цвет должен быть в формате HEX (например, #4b82f6)',
            'wip_limit.max' => 'WIP лимит не может превышать 100',
        ];
    }
}
