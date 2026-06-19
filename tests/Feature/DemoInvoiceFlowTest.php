<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        CompanyProfile::query()->first()->update(['fbr_business_nature' => 'distributor']);
        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');
        config()->set('fbr.endpoints.submit_invoice', '/submit');

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response([
                'validationResponse' => [
                    'statusCode' => '00',
                    'status' => 'Valid',
                    'error' => '',
                    'invoiceStatuses' => [[
                        'itemSNo' => '1',
                        'statusCode' => '00',
                        'status' => 'Valid',
                        'invoiceNo' => '',
                        'errorCode' => '',
                        'error' => '',
                    ]],
                ],
            ], 200),
            'https://sandbox.example.test/submit' => Http::response([
                'invoiceNumber' => '1234567DI202606190001',
                'validationResponse' => [
                    'statusCode' => '00',
                    'status' => 'Valid',
                    'error' => '',
                    'invoiceStatuses' => [[
                        'itemSNo' => '1',
                        'statusCode' => '00',
                        'status' => 'Valid',
                        'invoiceNo' => '1234567DI202606190001-1',
                        'errorCode' => '',
                        'error' => '',
                    ]],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.mock-fbr-console.demo-invoice'), [
            'scenario_code' => 'SN002',
        ]);

        $response->assertRedirect(route('admin.mock-fbr-console'));
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('fbr_api_logs', 2);
        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame(InvoiceStatus::Editable, $invoice->status);
        $this->assertSame('SN002', $invoice->scenario->code);
        $this->assertSame('unregistered', $invoice->customer->buyer_type->value);
        $this->assertSame('0101.2100', $invoice->items->first()->hs_code);
        $this->assertSame('Numbers, pieces, units', $invoice->items->first()->uom);
        $this->assertSame('Goods at standard rate (default)', $invoice->items->first()->sale_type);
        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://sandbox.example.test/validate'
                && $payload['scenarioId'] === 'SN002'
                && $payload['buyerRegistrationType'] === 'Unregistered'
                && $payload['items'][0]['hsCode'] === '0101.2100'
                && $payload['items'][0]['uoM'] === 'Numbers, pieces, units'
                && $payload['items'][0]['saleType'] === 'Goods at standard rate (default)';
        });
        $this->assertNotNull($invoice->qr_code_path);
        $this->assertNotNull($invoice->pdf_path);
        $this->assertFileExists(storage_path('app/public/'.$invoice->qr_code_path));
        $this->assertFileExists(storage_path('app/public/'.$invoice->pdf_path));
    }

    #[Test]
    public function admin_can_select_a_demo_scenario_from_the_console(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update(['fbr_business_nature' => 'distributor']);

        $this->actingAs($admin)
            ->get(route('admin.mock-fbr-console'))
            ->assertOk()
            ->assertSee('SN010 - Telecom services rendered or provided')
            ->assertSee('SN027 - Sale to End Consumer by retailers');
    }

    #[Test]
    public function demo_invoice_submission_is_blocked_when_company_profile_is_production(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update([
            'fbr_environment' => 'production',
            'fbr_business_nature' => 'distributor',
        ]);
        Http::fake();

        $response = $this->actingAs($admin)->post(route('admin.mock-fbr-console.demo-invoice'), [
            'scenario_code' => 'SN002',
        ]);

        $response->assertRedirect(route('admin.mock-fbr-console'));
        Http::assertNothingSent();
        $this->assertDatabaseCount('invoices', 0);
    }

    #[Test]
    public function selected_demo_scenario_controls_the_generated_payload(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        CompanyProfile::query()->first()->update(['fbr_business_nature' => 'distributor']);
        config()->set('fbr.sandbox_base_url', 'https://sandbox.example.test');
        config()->set('fbr.endpoints.validate_invoice', '/validate');
        config()->set('fbr.endpoints.submit_invoice', '/submit');

        Http::fake([
            'https://sandbox.example.test/validate' => Http::response(['validationResponse' => ['status' => 'Valid']], 200),
            'https://sandbox.example.test/submit' => Http::response(['invoiceNumber' => '1234567DI202606190002', 'validationResponse' => ['status' => 'Valid']], 200),
        ]);

        $this->actingAs($admin)->post(route('admin.mock-fbr-console.demo-invoice'), [
            'scenario_code' => 'SN010',
        ])->assertRedirect(route('admin.mock-fbr-console'));

        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('SN010', $invoice->scenario->code);
        $this->assertSame('Telecommunication services', $invoice->items->first()->sale_type);
        $this->assertSame(17.0, (float) $invoice->items->first()->rate_percent);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://sandbox.example.test/validate'
                && $payload['scenarioId'] === 'SN010'
                && $payload['items'][0]['saleType'] === 'Telecommunication services'
                && $payload['items'][0]['rate'] === '17%'
                && (float) $payload['items'][0]['salesTaxApplicable'] === 170.0;
        });
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
