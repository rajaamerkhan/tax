<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HsCode;
use App\Models\Province;
use App\Models\SaleType;
use App\Models\Uom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceReferenceOptionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_fetch_dynamic_invoice_reference_options(): void
    {
        $this->seed();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $uom = Uom::first() ?? Uom::create(['code' => 'KG', 'name' => 'KG', 'fbr_id' => '13']);
        $hsCode = HsCode::create([
            'code' => '2523.2910',
            'description' => 'PORTLAND CEMENT',
            'uom_id' => $uom->id,
            'is_active' => true,
        ]);
        $saleType = SaleType::first() ?? SaleType::create([
            'code' => 'STANDARD',
            'name' => 'Goods at standard rate (default)',
            'fbr_id' => '18',
        ]);
        $province = Province::first();

        Http::fake([
            'https://gw.fbr.gov.pk/pdi/v2/SaleTypeToRate*' => Http::response([
                ['ratE_ID' => 734, 'ratE_DESC' => '18%', 'ratE_VALUE' => 18],
            ]),
            'https://gw.fbr.gov.pk/pdi/v1/SroSchedule*' => Http::response([]),
        ]);

        $response = $this->actingAs($admin)->getJson(route('invoices.reference-options', [
            'hs_code_id' => $hsCode->id,
            'sale_type_id' => $saleType->id,
            'invoice_date' => '2026-06-05',
            'sale_origin_province_id' => $province->id,
        ]));

        $response->assertOk()
            ->assertJsonStructure(['uoms', 'rates', 'sroSchedules']);
    }
}
