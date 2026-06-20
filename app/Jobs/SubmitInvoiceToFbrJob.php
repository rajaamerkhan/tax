<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\FbrDigitalInvoiceService;
use App\Services\InvoiceSubmissionFinalizer;
use App\Support\AuditLogger;
use App\Support\FbrEnvironmentContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SubmitInvoiceToFbrJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $invoiceId) {}

    public function handle(FbrDigitalInvoiceService $service, InvoiceSubmissionFinalizer $finalizer, FbrEnvironmentContext $environmentContext): void
    {
        $invoice = Invoice::query()->with(['customer', 'items'])->findOrFail($this->invoiceId);
        if (! $environmentContext->isCurrent($invoice->environment)) {
            return;
        }

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
            'status' => InvoiceStatus::Failed,
            'error_message' => $exception?->getMessage(),
        ]);

        app(AuditLogger::class)->log('invoice.fbr_submit_failed', $invoice, null, ['error' => $exception?->getMessage()]);
    }
}
