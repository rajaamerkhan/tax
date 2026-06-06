<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsCodeImport extends Model
{
    protected $fillable = ['filename', 'status', 'imported_count', 'errors', 'created_by'];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
