<?php

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Card;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CardOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $card = Card::find($this->input('cardId'));

        if (! $card) {
            return true;
        }

        return $this->user()->can('update', $card);
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
