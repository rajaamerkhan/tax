<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Support\InvoiceCalculator;
use Illuminate\Validation\ValidationException;

class InvoiceItemObserver
{
    public function saving(InvoiceItem $invoiceItem): void
    {
        if ($invoiceItem->invoice && $invoiceItem->invoice->isLocked()) {
            throw ValidationException::withMessages([
                'invoice' => 'Locked invoices cannot be modified.',
            ]);
        }

        $invoiceItem->fill(app(InvoiceCalculator::class)->calculateItem($invoiceItem->getAttributes()));
    }

    public function saved(InvoiceItem $invoiceItem): void
    {
        $invoiceItem->invoice?->refresh()->load('items');
        $invoiceItem->invoice?->recalculateTotals();
    }

    public function deleted(InvoiceItem $invoiceItem): void
    {
        $invoiceItem->invoice?->refresh()->load('items');
        $invoiceItem->invoice?->recalculateTotals();
    }
}
