<?php

namespace App\Support;

use App\Models\CompanyProfile;

class FbrEnvironmentContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function current(?int $clientId = null): string
    {
        $environment = CompanyProfile::query()
            ->where('client_id', $clientId ?? $this->tenantContext->clientId())
            ->first()
            ?->fbr_environment?->value;

        return in_array($environment, ['sandbox', 'production'], true)
            ? $environment
            : (string) config('fbr.env', 'sandbox');
    }

    public function isCurrent(?string $environment, ?int $clientId = null): bool
    {
        return ($environment ?: 'sandbox') === $this->current($clientId);
    }
}
