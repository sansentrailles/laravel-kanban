<?php

declare(strict_types=1);

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Column;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColumnRequest extends FormRequest
{
    /**
     * Создание колонки в доске проверяет BoardPolicy::createColumn():
     * участник воркспейса, к которому принадлежит доска.
     * Роут-модель-биндинг гарантированно отдаёт Board (нет {board} — будет 404
     * ещё до вызова authorize), поэтому защитной ветки на null не нужно.
     */
    public function authorize(): bool
    {
        return $this->user()->can('createColumn', $this->route('board'));
    }

    public function rules(): array
    {
        $tableName = (new Column)->getTable();

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique($tableName, 'title')
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
            'title.unique' => 'Колонка с таким названием уже существует в этой доске',
            'color.regex' => 'Цвет должен быть в формате HEX (например, #4b82f6)',
            'wip_limit.max' => 'WIP лимит не может превышать 100',
        ];
    }
}
