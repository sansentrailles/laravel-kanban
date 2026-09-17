<?php

namespace App\Http\Requests\Kanban;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('card'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['jpeg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar', '7z', 'txt', 'md', 'xlsx', 'xls'])
                    ->max(20 * 1024), // Максимум 20Мб
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'Размен файла не должен превышать 20Мб',
            'file.mimes' => 'Недопустимый формат файла. Разрешены изображения, PDF, документы, архивы'
        ];
    }
}
