<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\FbrDigitalInvoiceService;
use App\Support\AuditLogger;
use App\Support\FbrEnvironmentContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ValidateInvoiceWithFbrJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $invoiceId) {}

    public function handle(FbrDigitalInvoiceService $service, FbrEnvironmentContext $environmentContext): void
    {
        $invoice = Invoice::query()->findOrFail($this->invoiceId);
        if (! $environmentContext->isCurrent($invoice->environment)) {
            return;
        }

        $response = $service->validateInvoice($invoice);
        $invoice->update([
            'status' => InvoiceStatus::Validated,
            'fbr_response_json' => $response,
            'error_message' => null,
        ]);

        app(AuditLogger::class)->log('invoice.fbr_validate_success', $invoice, null, $response);
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

        app(AuditLogger::class)->log('invoice.fbr_validate_failed', $invoice, null, ['error' => $exception?->getMessage()]);
    }
}
