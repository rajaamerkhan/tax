<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LockExpiredInvoicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function scheduler_command_locks_expired_invoices(): void
    {
        $this->seed();
        $user = User::first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-1',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'buyer_name' => 'ABC Traders',
            'status' => InvoiceStatus::Editable,
            'editable_until' => now()->subHour(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->artisan('invoices:lock-expired')->assertSuccessful();

        $this->assertEquals(InvoiceStatus::Locked, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->locked_at);
    }
}
