<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LockedInvoiceEditProtectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function locked_invoice_cannot_be_edited_after_lock(): void
    {
        $this->seed();

        $user = User::factory()->create(['role' => UserRole::Admin]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-2',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'buyer_name' => 'Buyer',
            'status' => InvoiceStatus::Draft,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Item',
            'quantity' => 1,
            'unit_price' => 100,
            'rate_percent' => 18,
        ]);
        $invoice->forceFill([
            'status' => InvoiceStatus::Locked,
            'editable_until' => now()->subDay(),
            'locked_at' => now()->subHour(),
        ])->saveQuietly();

        $payload = [
            'invoice_number' => 'INV-LOCK-2',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'buyer_name' => 'Updated Buyer',
            'items' => [
                ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'rate_percent' => 18],
            ],
        ];

        $response = $this->actingAs($user)->put(route('invoices.update', $invoice), $payload);

        $response->assertStatus(422);
        $this->assertSame('Buyer', $invoice->fresh()->buyer_name);
    }
}
