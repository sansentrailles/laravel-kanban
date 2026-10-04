<?php

namespace App\Http\Requests\Kanban;

use App\Models\Kanban\Card;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        // БЕЗОПАСНОСТЬ: Проверяем права на обновление конкретной карточки.
        // Если в роуте параметр называется {card}, используем $this->route('card').
        // Если {id}, используем $this->route('id').
        // $card = $this->route('card') ?? Card::findOrFail($this->route('id'));
        // return $this->user()->can('update', $card);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $card = $this->route('card') ?? Card::findOrFail($this->route('id'));
        $workspaceId = $card->workspace_id;

        return [
            // 'cardId' => 'required|integer|exists:kanban_cards,id',
            'description' => 'string|nullable',
            'title' => 'string|nullable',
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => [
                'integer',
                // КРИТИЧЕСКИ ВАЖНО: Метка должна существовать И принадлежать этому воркспейсу
                Rule::exists('kanban_labels', 'id')
                    ->where('workspace_id', $workspaceId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'label_ids.*.exists' => 'Одна или несколько меток не существуют или не принадлежат этому воркспейсу.',
            'priority.enum' => 'Выбран недопустимый приоритет.',
        ];
    }
}
