<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\FbrDigitalInvoiceService;
use App\Services\InvoiceSubmissionFinalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SubmitInvoiceToFbrJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $invoiceId)
    {
    }

    public function handle(FbrDigitalInvoiceService $service, InvoiceSubmissionFinalizer $finalizer): void
    {
        $invoice = Invoice::query()->with(['customer', 'items'])->findOrFail($this->invoiceId);
        $response = $service->submitInvoice($invoice);
        $finalizer->finalize($invoice, $response);
    }

    public function failed(?Throwable $exception): void
    {
        $invoice = Invoice::query()->find($this->invoiceId);
        if (! $invoice) {
            return;
        }

        $invoice->update([
            'status' => \App\Enums\InvoiceStatus::Failed,
            'error_message' => $exception?->getMessage(),
        ]);

        app(AuditLogger::class)->log('invoice.fbr_submit_failed', $invoice, null, ['error' => $exception?->getMessage()]);
    }
}
