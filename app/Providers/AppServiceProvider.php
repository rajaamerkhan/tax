<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Observers\InvoiceItemObserver;
use App\Observers\InvoiceObserver;
use App\Support\PakistanTaxHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);

        Validator::extend('cnic', function (string $attribute, mixed $value): bool {
            return is_string($value) && PakistanTaxHelper::isValidCnic($value);
        }, 'The CNIC must be a valid Pakistani CNIC.');

        Validator::extend('ntn', function (string $attribute, mixed $value): bool {
            return is_string($value) && PakistanTaxHelper::isValidNtn($value);
        }, 'The NTN must be a valid Pakistani NTN.');
    }
}
