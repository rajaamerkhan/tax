<?php

namespace App\Http\Requests;

use App\Support\FbrSandboxProfile;
use App\Support\PakistanTaxHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->canManageSettings() || $this->user()?->canManageClients()) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ntn_cnic' => ['required', 'ntn'],
            'strn' => ['nullable', 'string', 'max:100'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'fbr_token' => ['nullable', 'string'],
            'fbr_sandbox_token' => ['nullable', 'string'],
            'fbr_production_token' => ['nullable', 'string'],
            'fbr_environment' => ['required', 'in:sandbox,production'],
            'fbr_business_nature' => ['nullable', Rule::in(array_keys(FbrSandboxProfile::businessNatures()))],
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
