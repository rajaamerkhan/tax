<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceImportBatch extends Model
{
    protected $fillable = ['filename', 'preview_rows', 'errors', 'imported_count', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'preview_rows' => 'array',
            'errors' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
