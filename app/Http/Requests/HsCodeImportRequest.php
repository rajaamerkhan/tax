<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HsCodeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSettings() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ];
    }
}
