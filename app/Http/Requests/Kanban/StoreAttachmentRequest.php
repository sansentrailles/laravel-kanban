<?php

declare(strict_types=1);

namespace App\Http\Requests\Kanban;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
        // return $this->user()->can('update', $this->route('card'));
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['jpeg', 'png', 'jpg', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt', 'md', 'rar'])
                    ->max(10 * 1024), // Максимум 10 МБ
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'Размер файла не должен превышать 10 МБ.',
            'file.mimes' => 'Недопустимый формат файла.',
        ];
    }
}
