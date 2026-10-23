<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CardCreateRequest extends FormRequest
{
    /**
     * Право на создание карточки в колонке проверяет ColumnPolicy::createCard():
     * участник воркспейса, к которому принадлежит колонка.
     */
    public function authorize(): bool
    {
        return $this->user()->can('createCard', $this->route('column'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
