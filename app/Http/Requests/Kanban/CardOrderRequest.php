<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CardOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Реализовать проверку пользователя
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
            'cardId' => 'required|integer|exists:kanban_cards,id',
            'columnId' => 'required|integer|exists:kanban_columns,id',
            'prevCardId' => 'nullable|integer|exists:kanban_cards,id',
            'nextCardId' => 'nullable|integer|exists:kanban_cards,id',
        ];
    }
}
