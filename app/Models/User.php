<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['client_id', 'name', 'email', 'phone', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value === null ? null : Str::lower(trim($value));
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function belongsToClient(?int $clientId): bool
    {
        return $this->isOwner() || ($clientId !== null && (int) $this->client_id === (int) $clientId);
    }

    public function isManagingClient(): bool
    {
        return $this->isOwner() && session('managed_client_id') !== null;
    }

    public function canManageSettings(): bool
    {
        return $this->role === UserRole::Admin || $this->isManagingClient();
    }

    public function canManageClients(): bool
    {
        return $this->isOwner();
    }

    public function canEditInvoices(): bool
    {
        return $this->isManagingClient() || in_array($this->role, [UserRole::Admin, UserRole::Accountant], true);
    }
}
