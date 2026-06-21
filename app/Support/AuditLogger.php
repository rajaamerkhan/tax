<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function log(string $action, Model|string $entity, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null, ?string $ipAddress = null): void
    {
        $clientId = $entity instanceof Model && $entity->getAttribute('client_id')
            ? (int) $entity->getAttribute('client_id')
            : $this->tenantContext->clientId();

        AuditLog::create([
            'client_id' => $clientId,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entity instanceof Model ? $entity::class : $entity,
            'entity_id' => $entity instanceof Model ? $entity->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()?->ip(),
        ]);
    }
}
