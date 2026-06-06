<?php

namespace App\Models;

use App\Enums\BuyerType;
use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
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

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
