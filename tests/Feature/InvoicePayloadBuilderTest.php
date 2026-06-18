<?php

namespace Tests\Feature;

use App\Enums\BuyerType;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Province;
use App\Models\Scenario;
use App\Models\User;
use App\Support\InvoicePayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicePayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_the_fbr_sandbox_invoice_json_shape(): void
    {
        $sellerProvince = Province::firstOrCreate(['code' => 'PB'], ['name' => 'Punjab', 'fbr_code' => '01']);
        $buyerProvince = Province::firstOrCreate(['code' => 'SD'], ['name' => 'Sindh', 'fbr_code' => '02']);
        $scenario = Scenario::create(['code' => 'SN001', 'name' => 'Standard sale']);
        $user = User::factory()->create();

        CompanyProfile::create([
            'name' => 'Your Business Name',
            'ntn_cnic' => '0000000000000',
            'province_id' => $sellerProvince->id,
            'address' => 'Seller Address',
        ]);

        $customer = Customer::create([
            'name' => 'Buyer Business Name',
            'ntn_cnic' => '1111111111111',
            'buyer_type' => BuyerType::Registered,
            'province_id' => $buyerProvince->id,
            'address' => 'Buyer Address',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'invoice_date' => '2026-06-18',
            'invoice_type' => 'Sale Invoice',
            'scenario_id' => $scenario->id,
            'sale_origin_province_id' => $sellerProvince->id,
            'destination_province_id' => $buyerProvince->id,
            'customer_id' => $customer->id,
            'buyer_name' => 'Buyer Business Name',
            'buyer_ntn_cnic' => '1111111111111',
            'buyer_address' => 'Buyer Address',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'hs_code' => '0000.0000',
            'description' => 'Product Description',
            'rate_percent' => 18,
            'uom' => 'Numbers, pieces, units',
            'quantity' => 1,
            'unit_price' => 1180,
            'fixed_notified_value' => 0,
            'extra_tax' => 0,
            'further_tax' => 0,
            'fed_payable' => 0,
            'discount' => 0,
            'sale_type' => 'Goods at standard rate (default)',
        ]);

        $payload = app(InvoicePayloadBuilder::class)->build($invoice);

        $this->assertSame([
            'invoiceType' => 'Sale Invoice',
            'invoiceDate' => '2026-06-18',
            'sellerNTNCNIC' => '0000000000000',
            'sellerBusinessName' => 'Your Business Name',
            'sellerProvince' => 'Punjab',
            'sellerAddress' => 'Seller Address',
            'buyerNTNCNIC' => '1111111111111',
            'buyerBusinessName' => 'Buyer Business Name',
            'buyerProvince' => 'Sindh',
            'buyerAddress' => 'Buyer Address',
            'buyerRegistrationType' => 'Registered',
            'invoiceRefNo' => '',
            'scenarioId' => 'SN001',
            'items' => [[
                'hsCode' => '0000.0000',
                'productDescription' => 'Product Description',
                'rate' => '18%',
                'uoM' => 'Numbers, pieces, units',
                'quantity' => 1.0,
                'totalValues' => 1180.0,
                'valueSalesExcludingST' => 1000.0,
                'fixedNotifiedValueOrRetailPrice' => 0.0,
                'salesTaxApplicable' => 180.0,
                'salesTaxWithheldAtSource' => 0.0,
                'extraTax' => 0.0,
                'furtherTax' => 0.0,
                'sroScheduleNo' => '',
                'fedPayable' => 0.0,
                'discount' => 0.0,
                'saleType' => 'Goods at standard rate (default)',
                'sroItemSerialNo' => '',
            ]],
        ], $payload);
    }
}
