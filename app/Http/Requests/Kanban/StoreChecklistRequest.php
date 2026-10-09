<?php

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Checklist;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistRequest extends FormRequest
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
        $tableName = (new Checklist())->getTable();

        return [
            'title' => [
                'required',
                'string',
                Rule::unique($tableName, 'title')
                    ->where('card_id', $this->route('card')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Чеклист с таким названием уже существует у карточки.',
        ];
    }
}
