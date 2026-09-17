<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255', Rule::unique('kanban_workspaces', 'name')
            ],
            'description' => [
                'nullable', 'string', 'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Воркспейс с таким незванием уже существует',
            'name.max' => 'Название воркспейса не должно превышать 255 символов',
        ];
    }
}
