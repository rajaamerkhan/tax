<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageClients() ?? false;
    }

    public function rules(): array
    {
        $client = $this->route('client');
        $admin = $client?->users()->where('role', 'admin')->first();

        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($admin),
            ],
            'admin_phone' => ['nullable', 'string', 'max:100'],
            'admin_password' => [($client && $admin) ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
