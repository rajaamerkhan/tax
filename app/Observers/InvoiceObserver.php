<?php

namespace App\Observers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class InvoiceObserver
{
    public function updating(Invoice $invoice): void
    {
        $originalEditableUntil = $invoice->getOriginal('editable_until');
        $expired = $originalEditableUntil ? Carbon::parse($originalEditableUntil)->isPast() : false;

        if (
            $invoice->getOriginal('status') === InvoiceStatus::Locked->value
            || ($expired && $invoice->status !== InvoiceStatus::Locked)
        ) {
            throw ValidationException::withMessages([
                'invoice' => 'Locked invoices cannot be modified.',
            ]);
        }
    }

    public function updated(Invoice $invoice): void
    {
        if (in_array($invoice->status, [InvoiceStatus::Submitted, InvoiceStatus::Editable, InvoiceStatus::Locked], true)) {
            app(AuditLogger::class)->log(
                'invoice.updated_after_submission',
                $invoice,
                $invoice->getOriginal(),
                $invoice->getChanges(),
            );
        }
    }
}
