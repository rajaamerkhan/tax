<?php

namespace App\Support;

use App\Models\CompanyProfile;

class FbrEnvironmentContext
{
    public function current(): string
    {
        $environment = CompanyProfile::query()->first()?->fbr_environment?->value;

        return in_array($environment, ['sandbox', 'production'], true)
            ? $environment
            : (string) config('fbr.env', 'sandbox');
    }

    public function isCurrent(?string $environment): bool
    {
        return ($environment ?: 'sandbox') === $this->current();
    }
}
