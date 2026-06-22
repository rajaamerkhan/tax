<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFbrActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function validate_button_runs_fbr_validation_immediately(): void
    {
        $this->seed();
        config()->set('queue.default', 'redis');
        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $province = Province::query()->firstOrFail();
        CompanyProfile::query()->firstOrFail()->update([
            'fbr_sandbox_token' => 'sandbox-token',
            'province_id' => $province->id,
        ]);

        $invoice = Invoice::create([
            'client_id' => $admin->client_id,
            'invoice_number' => 'VALIDATE-NOW-1',
            'invoice_date' => '2026-05-01',
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'sale_origin_province_id' => $province->id,
            'destination_province_id' => $province->id,
            'buyer_name' => 'FBR INTERNAL',
            'buyer_ntn_cnic' => '9999999999999',
            'buyer_address' => 'Lahore',
            'status' => InvoiceStatus::Draft,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'CEMENT',
            'quantity' => 41000,
            'unit_price' => 26.809,
            'rate_percent' => 18,
            'hs_code' => '2523.2900',
            'uom' => 'KG',
            'sale_type' => '3rd Schedule Goods',
        ]);

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response([
                'validationResponse' => ['status' => 'Valid'],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.validate-fbr', $invoice))
            ->assertRedirect()
            ->assertSessionHas('status', 'FBR validation completed.');

        $this->assertSame(InvoiceStatus::Validated, $invoice->fresh()->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.example.test/validate');
    }

    #[Test]
    public function validate_button_stores_fbr_error_response_when_validation_fails(): void
    {
        $this->seed();
        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $province = Province::query()->firstOrFail();
        CompanyProfile::query()->firstOrFail()->update(['province_id' => $province->id]);
        $invoice = $this->invoice($admin, $province);

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response([
                'dated' => '2026-06-22 17:31:35',
                'sourceInvoiceNo' => '',
                'validationResponse' => [
                    'status' => 'Invalid',
                    'error' => 'Unauthorized',
                ],
            ], 401),
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.validate-fbr', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Failed, $invoice->status);
        $this->assertSame('Unauthorized', $invoice->fbr_response_json['validationResponse']['error']);

        $this->actingAs($admin)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Unauthorized')
            ->assertSee('sourceInvoiceNo');
    }

    #[Test]
    public function submit_button_stores_raw_fbr_response_when_gateway_returns_xml(): void
    {
        $this->seed();
        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.submit_invoice', '/submit');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $province = Province::query()->firstOrFail();
        CompanyProfile::query()->firstOrFail()->update(['province_id' => $province->id]);
        $invoice = $this->invoice($admin, $province);
        $xml = '<am:fault xmlns:am="http://wso2.org/apimanager"><am:code>404</am:code><am:type>Status report</am:type><am:message>Not Found</am:message></am:fault>';

        Http::fake([
            'https://sandbox.example.test/submit' => Http::response($xml, 404, ['Content-Type' => 'application/xml']),
        ]);

        $this->actingAs($admin)
            ->post(route('invoices.submit-fbr', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Failed, $invoice->status);
        $this->assertSame($xml, $invoice->fbr_response_json['raw_response']);

        $this->actingAs($admin)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('raw_response')
            ->assertSee('Not Found');
    }

    private function invoice(User $admin, Province $province): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $admin->client_id,
            'invoice_number' => 'VALIDATE-NOW-1',
            'invoice_date' => '2026-05-01',
            'invoice_type' => 'Sale Invoice',
            'environment' => 'sandbox',
            'sale_origin_province_id' => $province->id,
            'destination_province_id' => $province->id,
            'buyer_name' => 'FBR INTERNAL',
            'buyer_ntn_cnic' => '9999999999999',
            'buyer_address' => 'Lahore',
            'status' => InvoiceStatus::Draft,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'CEMENT',
            'quantity' => 41000,
            'unit_price' => 26.809,
            'rate_percent' => 18,
            'hs_code' => '2523.2900',
            'uom' => 'KG',
            'sale_type' => '3rd Schedule Goods',
        ]);

        return $invoice;
    }
}
