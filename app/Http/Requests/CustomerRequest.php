<?php

namespace App\Http\Requests;

use App\Support\PakistanTaxHelper;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditInvoices() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ntn_cnic' => ['nullable', 'ntn'],
            'strn' => ['nullable', 'string', 'max:100'],
            'buyer_type' => ['required', 'in:registered,unregistered'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ntn_cnic' => $this->filled('ntn_cnic')
                ? PakistanTaxHelper::normalizeNtn((string) $this->input('ntn_cnic'))
                : $this->input('ntn_cnic'),
        ]);
    }
}
