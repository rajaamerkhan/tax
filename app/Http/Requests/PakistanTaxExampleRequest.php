<?php

namespace App\Http\Requests;

use App\Rules\PakistanCnicRule;
use App\Rules\PakistanNtnRule;
use App\Support\PakistanTaxHelper;
use Illuminate\Foundation\Http\FormRequest;

class PakistanTaxExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnic' => ['required', new PakistanCnicRule()],
            'ntn' => ['required', new PakistanNtnRule()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnic' => $this->filled('cnic') ? PakistanTaxHelper::normalizeCnic((string) $this->input('cnic')) : $this->input('cnic'),
            'ntn' => $this->filled('ntn') ? PakistanTaxHelper::normalizeNtn((string) $this->input('ntn')) : $this->input('ntn'),
        ]);
    }
}
