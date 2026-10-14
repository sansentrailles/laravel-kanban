<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // $card = $this->route('card');

        // return $this->user()->can('update', $card);
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
            'content' => [
                'required',
                'string',
                'max:5000',
                'min:1',
            ],
        ];
    }

    /**
     * Кастомные сообщения об ошибках валидации.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Комментарий не может быть пустым.',
            'content.max' => 'Комментарий не должен превышать 5000 символов.',
            'content.min' => 'Комментарий должен содержать хотя бы один символ.',
        ];
    }
}
