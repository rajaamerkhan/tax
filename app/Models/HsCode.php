<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsCode extends Model
{
    protected $fillable = ['code', 'description', 'uom_id', 'fbr_item_code', 'custom_duty_code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }
}
