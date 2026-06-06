<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'hs_code_id', 'uom_id', 'tax_rate_id', 'sale_type_id', 'sro_schedule_id',
        'hs_code', 'description', 'uom', 'quantity', 'unit_price', 'rate_percent', 'value_excluding_sales_tax',
        'sales_tax', 'extra_tax', 'further_tax', 'fed_payable', 'discount', 'fixed_notified_value',
        'sale_type', 'sro_schedule_number', 'item_serial_number', 'total_value',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function hsCodeRelation(): BelongsTo
    {
        return $this->belongsTo(HsCode::class, 'hs_code_id');
    }

    public function uomRelation(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
