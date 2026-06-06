<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\HsCode;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_search_invoice_autocomplete_resources(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = Customer::create([
            'name' => 'Acme Traders',
            'ntn_cnic' => '1234567-8',
            'strn' => '9988776655443',
            'address' => 'Karachi',
            'status' => 'active',
        ]);
        $hsCode = HsCode::create([
            'code' => '9999.9998',
            'description' => 'TEST AUTOCOMPLETE ITEM',
            'custom_duty_code' => '0',
            'is_active' => true,
        ]);
        $saleType = SaleType::first() ?? SaleType::create([
            'code' => 'STANDARD',
            'name' => 'Goods at standard rate (default)',
            'fbr_id' => '18',
        ]);

        $this->actingAs($admin)
            ->getJson(route('invoices.autocomplete', ['resource' => 'customers', 'q' => 'Acme']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $customer->id);

        $hsResponse = $this->actingAs($admin)
            ->getJson(route('invoices.autocomplete', ['resource' => 'hs-codes', 'q' => '9999.9998']))
            ->assertOk();

        $this->assertContains($hsCode->id, collect($hsResponse->json('results'))->pluck('id')->all());

        $this->actingAs($admin)
            ->getJson(route('invoices.autocomplete', ['resource' => 'sale-types', 'q' => 'standard']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $saleType->id);
    }
}
