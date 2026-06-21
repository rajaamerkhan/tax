<?php

namespace App\Models;

use App\Enums\BuyerType;
use App\Enums\CustomerStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'client_id',
        'ntn_cnic',
        'strn',
        'buyer_type',
        'province_id',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'buyer_type' => BuyerType::class,
            'status' => CustomerStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            $customer->client_id ??= Auth::user()?->client_id
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

    public function scopeForClient(Builder $query, ?int $clientId): Builder
    {
        return $query->where($query->getModel()->getTable().'.client_id', $clientId);
    }
}
