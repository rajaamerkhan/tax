<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

class LockExpiredInvoices extends Command
{
    protected $signature = 'invoices:lock-expired';

    protected $description = 'Lock submitted invoices whose 72-hour editable window has expired.';

    public function handle(): int
    {
        $count = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Submitted->value, InvoiceStatus::Editable->value])
            ->whereNotNull('editable_until')
            ->where('editable_until', '<=', now())
            ->chunkById(100, function ($invoices) use (&$count): void {
                foreach ($invoices as $invoice) {
                    if ($invoice->lockIfExpired()) {
                        app(AuditLogger::class)->log('invoice.locked', $invoice, null, ['status' => InvoiceStatus::Locked->value]);
                        $count++;
                    }
                }
            });

        $this->info("Locked {$count} expired invoice(s).");

        return self::SUCCESS;
    }
}
