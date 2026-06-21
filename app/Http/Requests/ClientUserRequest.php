<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSettings() ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $creating = ! $user;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'phone' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in([
                UserRole::Admin->value,
                UserRole::Accountant->value,
                UserRole::Viewer->value,
            ])],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function clientId(): ?int
    {
        return app(TenantContext::class)->clientId($this->user());
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
