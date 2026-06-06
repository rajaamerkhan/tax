<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\FbrApiLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Province;
use App\Models\User;
use App\Services\FbrDigitalInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FbrDigitalInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_bearer_authenticated_validate_request(): void
    {
        $this->seed();

        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');

        $province = Province::first();
        CompanyProfile::query()->first()->update(['fbr_token' => 'secret-token', 'province_id' => $province->id]);

        $user = User::first();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-FBR-1',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'sale_origin_province_id' => $province->id,
            'destination_province_id' => $province->id,
            'buyer_name' => 'Buyer',
            'buyer_ntn_cnic' => '1234567-8',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'OPC Cement',
            'quantity' => 10,
            'unit_price' => 100,
            'rate_percent' => 18,
        ]);
        $invoice->refresh()->load('items');
        $invoice->recalculateTotals();

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response(['ok' => true], 200),
        ]);

        $response = app(FbrDigitalInvoiceService::class)->validateInvoice($invoice);

        $this->assertTrue($response['ok']);
        $this->assertDatabaseHas('fbr_api_logs', [
            'invoice_id' => $invoice->id,
            'endpoint' => '/validate',
            'http_status' => 200,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.example.test/validate'
                && $request->hasHeader('Authorization', 'Bearer secret-token');
        });
    }
}
