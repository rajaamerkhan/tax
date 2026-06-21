<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FbrApiLog extends Model
{
    protected $fillable = [
        'client_id', 'invoice_id', 'user_id', 'endpoint', 'method', 'environment', 'http_status', 'status',
        'request_payload', 'response_payload', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FbrApiLog $log): void {
            $log->client_id ??= Auth::user()?->client_id
                ?? Invoice::query()->whereKey($log->invoice_id)->value('client_id')
                ?? (Client::query()->count() === 1 ? Client::query()->value('id') : null);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
