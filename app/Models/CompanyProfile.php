<?php

namespace App\Models;

use App\Enums\FbrEnvironment;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client_id',
        'ntn_cnic',
        'fbr_registration_number',
        'strn',
        'province_id',
        'address',
        'phone',
        'email',
        'fbr_token',
        'fbr_sandbox_token',
        'fbr_production_token',
        'fbr_environment',
        'fbr_business_nature',
    ];

    protected function casts(): array
    {
        return [
            'fbr_token' => 'encrypted',
            'fbr_sandbox_token' => 'encrypted',
            'fbr_production_token' => 'encrypted',
            'fbr_environment' => FbrEnvironment::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CompanyProfile $companyProfile): void {
            $companyProfile->client_id ??= Auth::user()?->client_id
                ?? (Client::query()->count() === 1 ? Client::query()->value('id') : null);
        });
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
