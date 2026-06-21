<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Support\InvoiceCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'invoice_number', 'invoice_date', 'invoice_type', 'environment', 'scenario_id', 'sale_origin_province_id',
        'destination_province_id', 'customer_id', 'buyer_name', 'buyer_ntn_cnic', 'buyer_strn',
        'buyer_address', 'notes', 'status', 'fbr_invoice_id', 'fbr_submitted_at', 'editable_until', 'locked_at',
        'fbr_response_json', 'error_message', 'qr_code_path', 'pdf_path', 'value_excluding_sales_tax', 'sales_tax_amount',
        'extra_tax_amount', 'further_tax_amount', 'fed_amount', 'discount_amount', 'grand_total',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'fbr_submitted_at' => 'datetime',
            'editable_until' => 'datetime',
            'locked_at' => 'datetime',
            'fbr_response_json' => 'array',
            'status' => InvoiceStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            $invoice->client_id ??= Auth::user()?->client_id
                ?? User::query()->whereKey($invoice->created_by)->value('client_id')
                ?? (Client::query()->count() === 1 ? Client::query()->value('id') : null);
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function saleOriginProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'sale_origin_province_id');
    }

    public function destinationProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'destination_province_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isLocked(): bool
    {
        return $this->status === InvoiceStatus::Locked || ($this->editable_until && $this->editable_until->isPast());
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked() && in_array($this->status, [InvoiceStatus::Draft, InvoiceStatus::Validated, InvoiceStatus::Failed, InvoiceStatus::Editable], true);
    }

    public function markSubmitted(array $response, ?string $fbrInvoiceId = null): void
    {
        $submittedAt = now();

        $this->forceFill([
            'status' => InvoiceStatus::Editable,
            'fbr_invoice_id' => $fbrInvoiceId,
            'fbr_submitted_at' => $submittedAt,
            'editable_until' => $submittedAt->copy()->addHours(72),
            'fbr_response_json' => $response,
            'error_message' => null,
        ])->save();
    }

    public function lockIfExpired(?Carbon $now = null): bool
    {
        $now ??= now();

        if ($this->status !== InvoiceStatus::Locked && $this->editable_until && $this->editable_until->lte($now)) {
            $this->forceFill([
                'status' => InvoiceStatus::Locked,
                'locked_at' => $now,
            ])->save();

            return true;
        }

        return false;
    }

    public function recalculateTotals(): void
    {
        $calculator = app(InvoiceCalculator::class);
        $summary = $calculator->summarize(
            $this->items->map(fn (InvoiceItem $item) => $item->only([
                'value_excluding_sales_tax', 'sales_tax', 'extra_tax', 'further_tax', 'fed_payable', 'discount', 'total_value',
            ]))->all(),
        );

        $this->forceFill($summary)->save();
    }

    public function scopeForClient(Builder $query, ?int $clientId): Builder
    {
        return $query->where($query->getModel()->getTable().'.client_id', $clientId);
    }
}
