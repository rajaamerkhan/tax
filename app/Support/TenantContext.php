<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    public function clientId(?User $user = null): ?int
    {
        $user ??= Auth::user();

        if ($user?->isOwner()) {
            $managedClientId = session('managed_client_id');

            return $managedClientId ? (int) $managedClientId : null;
        }

        return $user?->client_id;
    }

    public function client(?User $user = null): ?Client
    {
        $user ??= Auth::user();

        if ($user?->isOwner()) {
            $clientId = $this->clientId($user);

            return $clientId ? Client::find($clientId) : null;
        }

        return $user?->client;
    }

    public function isManagingClient(?User $user = null): bool
    {
        return $user?->isOwner() && $this->clientId($user) !== null;
    }

    public function clientUser(?User $user = null): ?User
    {
        $user ??= Auth::user();

        if (! $this->isManagingClient($user)) {
            return $user;
        }

        return User::query()
            ->where('client_id', $this->clientId($user))
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [UserRole::Admin->value])
            ->oldest()
            ->first();
    }

    public function companyProfileQuery(?int $clientId = null)
    {
        return \App\Models\CompanyProfile::query()->where('client_id', $clientId ?? $this->clientId());
    }

    public function authorizeModel(Model $model, ?User $user = null): void
    {
        $user ??= Auth::user();

        $clientId = $model instanceof Invoice
            ? $this->invoiceClientId($model)
            : $model->getAttribute('client_id');

        abort_unless($user && ($user->isOwner() || (int) $clientId === (int) $this->clientId($user)), 404);
    }

    public function invoiceClientId(Invoice $invoice): ?int
    {
        return $invoice->client_id ?: $invoice->creator?->client_id;
    }
}
