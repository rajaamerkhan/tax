<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_submit_demo_invoice_to_mock_fbr(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.mock-fbr-console.demo-invoice'));

        $response->assertRedirect(route('admin.mock-fbr-console'));
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('fbr_api_logs', 2);
        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame(InvoiceStatus::Editable, $invoice->status);
        $this->assertNotNull($invoice->qr_code_path);
        $this->assertNotNull($invoice->pdf_path);
        $this->assertFileExists(storage_path('app/public/'.$invoice->qr_code_path));
        $this->assertFileExists(storage_path('app/public/'.$invoice->pdf_path));
    }

    #[Test]
    public function admin_can_download_hs_template(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('reference-data.hs-codes.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
