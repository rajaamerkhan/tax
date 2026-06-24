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
            'fbr_registration_number' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! PakistanTaxHelper::isValidFbrSellerRegistration($value)) {
                        $fail('Enter the seller CNIC or FBR portal Registration No., for example 3520212345671 or F518891.');
                    }
                },
            ],
            'ntn_cnic' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! PakistanTaxHelper::isValidSellerTaxNumber($value)) {
                        $fail('Enter the seller Tax Number/NTN, for example F518891-5 or 4174941-3.');
                    }
                },
            ],
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
            'fbr_registration_number' => $this->filled('fbr_registration_number')
                ? PakistanTaxHelper::normalizeFbrSellerRegistration((string) $this->input('fbr_registration_number'))
                : $this->input('fbr_registration_number'),
            'ntn_cnic' => $this->filled('ntn_cnic')
                ? PakistanTaxHelper::normalizeSellerTaxNumber((string) $this->input('ntn_cnic'))
                : $this->input('ntn_cnic'),
        ]);
    }
}
