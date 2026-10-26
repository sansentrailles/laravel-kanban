<?php

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Card;
use App\Models\Kanban\Column;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $card = Card::find($this->input('cardId'));
        $column = Column::find($this->input('columnId'));

        if (! $card || ! $column) {
            return false;
        }

        return $this->user()->can('update', $card)
            && $this->user()->can('update', $column)
            && $card->column->board_id === $column->board_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetColumnId = $this->input('columnId');

        return [
            'cardId' => 'required|integer|exists:kanban_cards,id',
            'columnId' => 'required|integer|exists:kanban_columns,id',
            'prevCardId' => [
                'nullable',
                'integer',
                Rule::exists('kanban_cards', 'id')->where('column_id', $targetColumnId),
            ],
            'nextCardId' => [
                'nullable',
                'integer',
                Rule::exists('kanban_cards', 'id')->where('column_id', $targetColumnId),
            ],
        ];
    }
}
