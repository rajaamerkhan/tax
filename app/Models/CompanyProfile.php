<?php

namespace App\Models;

use App\Enums\FbrEnvironment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ntn_cnic',
        'strn',
        'province_id',
        'address',
        'phone',
        'email',
        'fbr_token',
        'fbr_environment',
        'fbr_business_nature',
    ];

    protected function casts(): array
    {
        return [
            'fbr_token' => 'encrypted',
            'fbr_environment' => FbrEnvironment::class,
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
