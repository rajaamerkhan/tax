<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Client;
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
        CompanyProfile::query()->first()->update(['fbr_sandbox_token' => 'portal-token', 'province_id' => $province->id]);

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
                && $request->hasHeader('Authorization', 'Bearer portal-token');
        });
    }

    #[Test]
    public function it_uses_production_invoice_endpoint_when_environment_is_production(): void
    {
        $this->seed();

        config()->set('fbr.production_base_url', 'https://gw.example.test');
        config()->set('fbr.endpoints.submit_invoice_production', '/di_data/v1/di/postinvoicedata');

        CompanyProfile::query()->first()->update(['fbr_environment' => 'production', 'fbr_production_token' => 'portal-production-token']);

        $invoice = $this->createInvoice();

        Http::fake([
            'https://gw.example.test/di_data/v1/di/postinvoicedata' => Http::response(['ok' => true], 200),
        ]);

        $response = app(FbrDigitalInvoiceService::class)->submitInvoice($invoice);

        $this->assertTrue($response['ok']);
        $this->assertDatabaseHas('fbr_api_logs', [
            'invoice_id' => $invoice->id,
            'endpoint' => '/di_data/v1/di/postinvoicedata',
            'http_status' => 200,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://gw.example.test/di_data/v1/di/postinvoicedata'
                && $request->hasHeader('Authorization', 'Bearer portal-production-token');
        });
    }

    #[Test]
    public function it_does_not_use_env_security_token_when_company_profile_token_is_blank(): void
    {
        $this->seed();

        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');
        config()->set('fbr.security_token', 'env-token');

        $invoice = $this->createInvoice();

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response(['ok' => true], 200),
        ]);

        $response = app(FbrDigitalInvoiceService::class)->validateInvoice($invoice);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.example.test/validate'
                && ! $request->hasHeader('Authorization');
        });
    }

    #[Test]
    public function it_does_not_send_authorization_header_for_placeholder_token(): void
    {
        $this->seed();

        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');
        config()->set('fbr.security_token', '');
        CompanyProfile::query()->first()->update(['fbr_sandbox_token' => 'N/A']);

        $invoice = $this->createInvoice();

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response(['ok' => true], 200),
        ]);

        $response = app(FbrDigitalInvoiceService::class)->validateInvoice($invoice);

        $this->assertTrue($response['ok']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.example.test/validate'
                && ! $request->hasHeader('Authorization');
        });
    }

    #[Test]
    public function it_uses_the_invoice_clients_fbr_token_only(): void
    {
        $this->seed();

        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');
        config()->set('fbr.security_token', 'global-token-must-not-be-used');

        CompanyProfile::query()->first()->update(['fbr_sandbox_token' => 'client-a-token']);

        $clientB = Client::factory()->create(['name' => 'Client B']);
        $userB = User::factory()->create(['client_id' => $clientB->id]);
        $province = Province::first();
        CompanyProfile::create([
            'client_id' => $clientB->id,
            'name' => 'Client B Seller',
            'ntn_cnic' => '7654321-0',
            'province_id' => $province->id,
            'fbr_environment' => 'sandbox',
            'fbr_sandbox_token' => 'client-b-token',
        ]);

        $invoice = Invoice::create([
            'client_id' => $clientB->id,
            'invoice_number' => 'INV-FBR-CLIENT-B',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'sale_origin_province_id' => $province->id,
            'destination_province_id' => $province->id,
            'buyer_name' => 'Buyer',
            'buyer_ntn_cnic' => '1234567-8',
            'status' => 'draft',
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'OPC Cement',
            'quantity' => 10,
            'unit_price' => 100,
            'rate_percent' => 18,
        ]);

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response(['ok' => true], 200),
        ]);

        app(FbrDigitalInvoiceService::class)->validateInvoice($invoice);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.example.test/validate'
                && $request->hasHeader('Authorization', 'Bearer client-b-token')
                && ! $request->hasHeader('Authorization', 'Bearer client-a-token')
                && ! $request->hasHeader('Authorization', 'Bearer global-token-must-not-be-used');
        });
    }

    #[Test]
    public function it_uses_documented_statl_query_parameters(): void
    {
        $this->seed();

        config()->set('fbr.sandbox_base_url', 'https://gw.example.test');
        config()->set('fbr.endpoints.st_atl', '/dist/v1/statl');

        Http::fake([
            'https://gw.example.test/dist/v1/statl*' => Http::response(['status' => 'Active'], 200),
        ]);

        $response = app(FbrDigitalInvoiceService::class)->verifyStAtl('0788762', '2025-05-18');

        $this->assertSame('Active', $response['status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://gw.example.test/dist/v1/statl?regno=0788762&date=2025-05-18';
        });
    }

    #[Test]
    public function it_uses_documented_registration_type_query_parameter(): void
    {
        $this->seed();

        config()->set('fbr.sandbox_base_url', 'https://gw.example.test');
        config()->set('fbr.endpoints.registration_type', '/dist/v1/Get_Reg_Type');

        Http::fake([
            'https://gw.example.test/dist/v1/Get_Reg_Type*' => Http::response(['REGISTRATION_TYPE' => 'Registered'], 200),
        ]);

        $response = app(FbrDigitalInvoiceService::class)->registrationType('0788762');

        $this->assertSame('Registered', $response['REGISTRATION_TYPE']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://gw.example.test/dist/v1/Get_Reg_Type?Registration_No=0788762';
        });
    }

    private function createInvoice(): Invoice
    {
        $province = Province::first();
        $user = User::first();

        CompanyProfile::query()->first()->update(['province_id' => $province->id]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-FBR-'.uniqid(),
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

        return $invoice;
    }
}
