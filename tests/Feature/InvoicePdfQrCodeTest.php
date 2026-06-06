<?php

namespace Tests\Feature;

use App\Enums\BuyerType;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Province;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicePdfQrCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function print_view_embeds_qr_code_as_base64_image(): void
    {
        $this->seed();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $province = Province::firstOrFail();
        $scenario = Scenario::firstOrFail();

        $customer = Customer::create([
            'name' => 'QR Buyer',
            'ntn_cnic' => '12345-6789012-3',
            'strn' => 'STRN-QR',
            'buyer_type' => BuyerType::Registered,
            'province_id' => $province->id,
            'address' => 'QR Address',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260606-0099',
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'scenario_id' => $scenario->id,
            'sale_origin_province_id' => $province->id,
            'destination_province_id' => $province->id,
            'customer_id' => $customer->id,
            'buyer_name' => $customer->name,
            'buyer_ntn_cnic' => $customer->ntn_cnic,
            'buyer_strn' => $customer->strn,
            'buyer_address' => $customer->address,
            'status' => InvoiceStatus::Editable,
            'fbr_invoice_id' => 'FBR-123456',
            'value_excluding_sales_tax' => 84.75,
            'sales_tax_amount' => 15.25,
            'extra_tax_amount' => 0,
            'further_tax_amount' => 0,
            'fed_amount' => 0,
            'discount_amount' => 0,
            'grand_total' => 100,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'hs_code' => '0102.3100',
            'description' => 'Pure-bred breeding animals',
            'uom' => 'KG',
            'quantity' => 1,
            'unit_price' => 100,
            'rate_percent' => 18,
            'value_excluding_sales_tax' => 84.75,
            'sales_tax' => 15.25,
            'extra_tax' => 0,
            'further_tax' => 0,
            'fed_payable' => 0,
            'discount' => 0,
            'fixed_notified_value' => 0,
            'sale_type' => 'Goods at standard rate',
            'sro_schedule_number' => 'SRO-1',
            'item_serial_number' => '1',
            'total_value' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('FBR Invoice ID:')
            ->assertSee('Click to verify')
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee('FBR-123456');
    }
}
