<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class InvoiceImportBatch extends Model
{
    protected $fillable = ['client_id', 'filename', 'preview_rows', 'errors', 'imported_count', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'preview_rows' => 'array',
            'errors' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InvoiceImportBatch $batch): void {
            $batch->client_id ??= Auth::user()?->client_id
                ?? (Client::query()->count() === 1 ? Client::query()->value('id') : null);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
