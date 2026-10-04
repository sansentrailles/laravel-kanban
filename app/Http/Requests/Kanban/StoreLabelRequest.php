<?php

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Label;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        // return $this->user()->can('update', $this->route('workspace'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tableName = (new Label)->getTable();

        return [
            'name' => [
                'required',
                'string',
                Rule::unique($tableName, 'name')
                    ->where('workspace_id', $this->route('workspace')->id),
            ],
            'color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Метка с таким названием уже существует в этом воркспейсе.',
            'color.regex' => 'Цвет должен быть в формате HEX (например, #3b82f6).',
        ];
    }
}
