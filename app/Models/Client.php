<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'status',
        'max_invoices_per_month',
    ];

    protected function casts(): array
    {
        return [
            'max_invoices_per_month' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function invoiceCountForMonth(?Carbon $month = null, ?string $environment = null): int
    {
        $month ??= now();

        return $this->invoices()
            ->whereNull('invoices.deleted_at')
            ->when($environment !== null, fn ($query) => $query->where('environment', $environment))
            ->whereBetween('invoice_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->count();
    }

    public function remainingInvoicesForMonth(?Carbon $month = null, ?string $environment = null): int
    {
        return max(((int) $this->max_invoices_per_month) - $this->invoiceCountForMonth($month, $environment), 0);
    }
}
