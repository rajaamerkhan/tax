<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function invoice_index_sorts_most_recently_created_invoices_first(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $newerDocumentImportedEarlier = $this->invoice('NEWER-DOCUMENT-IMPORTED-EARLIER', '2026-06-02', '2026-06-02 09:00:00');
        $olderDocumentImportedLater = $this->invoice('OLDER-DOCUMENT-IMPORTED-LATER', '2026-05-01', '2026-06-02 10:00:00');
        $sameImportTimeNewerDocument = $this->invoice('SAME-IMPORT-TIME-NEWER-DOCUMENT', '2026-06-01', '2026-06-02 08:00:00');
        $sameImportTimeOlderDocument = $this->invoice('SAME-IMPORT-TIME-OLDER-DOCUMENT', '2026-05-01', '2026-06-02 08:00:00');

        $this->actingAs($admin)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $olderDocumentImportedLater->invoice_number,
                $newerDocumentImportedEarlier->invoice_number,
                $sameImportTimeNewerDocument->invoice_number,
                $sameImportTimeOlderDocument->invoice_number,
            ]);
    }

    private function invoice(string $number, string $invoiceDate, string $createdAt): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => $number,
            'invoice_date' => $invoiceDate,
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'buyer_name' => 'Buyer '.$number,
            'status' => 'draft',
        ]);

        $invoice->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $invoice;
    }
}
