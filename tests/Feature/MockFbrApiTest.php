<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Support\FbrDemoScenarioFixtures;
use App\Support\FbrSandboxProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MockFbrApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mock_validate_endpoint_returns_pdf_shaped_valid_response(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');

        $payload = [
            'invoiceType' => 'Sale Invoice',
            'invoiceDate' => '2025-04-21',
            'sellerNTNCNIC' => '1234567',
            'sellerBusinessName' => 'Company 8',
            'sellerProvince' => 'Sindh',
            'sellerAddress' => 'Karachi',
            'buyerNTNCNIC' => '1234567890123',
            'buyerBusinessName' => 'Buyer',
            'buyerProvince' => 'Sindh',
            'buyerAddress' => 'Karachi',
            'buyerRegistrationType' => 'Registered',
            'invoiceRefNo' => '',
            'scenarioId' => 'SN001',
            'items' => [[
                'hsCode' => '0101.2100',
                'productDescription' => 'product Description',
                'rate' => '18%',
                'uoM' => 'Numbers, pieces, units',
                'quantity' => 1.0000,
                'totalValues' => 1180.00,
                'valueSalesExcludingST' => 1000.00,
                'fixedNotifiedValueOrRetailPrice' => 0.00,
                'salesTaxApplicable' => 180.00,
                'salesTaxWithheldAtSource' => 0.00,
                'extraTax' => 0.00,
                'furtherTax' => 0.00,
                'sroScheduleNo' => '',
                'fedPayable' => 0.00,
                'discount' => 0.00,
                'saleType' => 'Goods at standard rate (default)',
                'sroItemSerialNo' => '',
            ]],
        ];

        $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
            ->postJson('/api/mock/fbr/di_data/v1/di/validateinvoicedata_sb', $payload);

        $response->assertOk()
            ->assertJsonPath('validationResponse.statusCode', '00')
            ->assertJsonPath('validationResponse.status', 'Valid')
            ->assertJsonPath('validationResponse.invoiceStatuses.0.statusCode', '00');
    }

    #[Test]
    public function mock_post_endpoint_returns_pdf_shaped_invalid_response_when_rate_is_missing(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');

        $payload = [
            'invoiceType' => 'Sale Invoice',
            'invoiceDate' => '2025-04-21',
            'sellerNTNCNIC' => '1234567',
            'sellerBusinessName' => 'Company 8',
            'sellerProvince' => 'Sindh',
            'sellerAddress' => 'Karachi',
            'buyerNTNCNIC' => '1234567890123',
            'buyerBusinessName' => 'Buyer',
            'buyerProvince' => 'Sindh',
            'buyerAddress' => 'Karachi',
            'buyerRegistrationType' => 'Registered',
            'invoiceRefNo' => '',
            'scenarioId' => 'SN001',
            'items' => [[
                'hsCode' => '0101.2100',
                'productDescription' => 'product Description',
                'rate' => '',
                'uoM' => 'Numbers, pieces, units',
                'quantity' => 1.0000,
                'totalValues' => 1180.00,
                'valueSalesExcludingST' => 1000.00,
                'fixedNotifiedValueOrRetailPrice' => 0.00,
                'salesTaxApplicable' => 180.00,
                'salesTaxWithheldAtSource' => 0.00,
                'saleType' => 'Goods at standard rate (default)',
            ]],
        ];

        $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
            ->postJson('/api/mock/fbr/di_data/v1/di/postinvoicedata_sb', $payload);

        $response->assertOk()
            ->assertJsonPath('validationResponse.status', 'Invalid')
            ->assertJsonPath('validationResponse.invoiceStatuses.0.errorCode', '0046');
    }

    #[Test]
    public function mock_validate_endpoint_rejects_scenarios_not_enabled_for_sandbox_profile(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');
        config()->set('fbr.mock.allowed_scenarios', ['SN001', 'SN002']);

        $payload = $this->validPayload([
            'scenarioId' => 'SN010',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
            ->postJson('/api/mock/fbr/di_data/v1/di/validateinvoicedata_sb', $payload);

        $response->assertOk()
            ->assertJsonPath('validationResponse.status', 'Invalid')
            ->assertJsonPath('validationResponse.invoiceStatuses.0.errorCode', 'MOCK_SCENARIO_NOT_ALLOWED');
    }

    #[Test]
    public function mock_validate_endpoint_rejects_sale_types_not_enabled_for_sandbox_profile(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');
        config()->set('fbr.mock.allowed_sale_types', ['Goods at standard rate', 'Goods at standard rate (default)']);

        $payload = $this->validPayload([
            'items' => [[
                'hsCode' => '0101.2100',
                'productDescription' => 'product Description',
                'rate' => '18%',
                'uoM' => 'Numbers, pieces, units',
                'quantity' => 1.0000,
                'totalValues' => 1180.00,
                'valueSalesExcludingST' => 1000.00,
                'fixedNotifiedValueOrRetailPrice' => 0.00,
                'salesTaxApplicable' => 180.00,
                'salesTaxWithheldAtSource' => 0.00,
                'saleType' => 'Telecommunication services',
            ]],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
            ->postJson('/api/mock/fbr/di_data/v1/di/validateinvoicedata_sb', $payload);

        $response->assertOk()
            ->assertJsonPath('validationResponse.status', 'Invalid')
            ->assertJsonPath('validationResponse.invoiceStatuses.0.errorCode', 'MOCK_SALE_TYPE_NOT_ALLOWED');
    }

    #[Test]
    public function mock_validate_endpoint_allows_all_sector_rules_for_distributor_profile(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');
        config()->set('fbr.mock.allowed_scenarios', ['SN001', 'SN002']);
        config()->set('fbr.mock.allowed_sale_types', ['Goods at standard rate (default)']);

        CompanyProfile::create([
            'name' => 'Demo Seller',
            'ntn_cnic' => '1234567-8',
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ]);

        $payload = $this->validPayload([
            'scenarioId' => 'SN010',
            'items' => [[
                'hsCode' => '0101.2100',
                'productDescription' => 'product Description',
                'rate' => '18%',
                'uoM' => 'Numbers, pieces, units',
                'quantity' => 1.0000,
                'totalValues' => 1180.00,
                'valueSalesExcludingST' => 1000.00,
                'fixedNotifiedValueOrRetailPrice' => 0.00,
                'salesTaxApplicable' => 180.00,
                'salesTaxWithheldAtSource' => 0.00,
                'saleType' => 'Telecommunication services',
            ]],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
            ->postJson('/api/mock/fbr/di_data/v1/di/validateinvoicedata_sb', $payload);

        $response->assertOk()
            ->assertJsonPath('validationResponse.status', 'Valid')
            ->assertJsonPath('validationResponse.invoiceStatuses.0.statusCode', '00');
    }

    #[Test]
    public function every_distributor_demo_fixture_validates_against_the_mock_sandbox_endpoint(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');

        CompanyProfile::create([
            'name' => 'Demo Seller',
            'ntn_cnic' => '1234567',
            'fbr_environment' => 'sandbox',
            'fbr_business_nature' => 'distributor',
        ]);

        $scenarioCodes = FbrSandboxProfile::allowedScenariosForBusinessNature('distributor');
        sort($scenarioCodes);

        $this->assertSame(
            array_map(fn (int $number): string => 'SN'.str_pad((string) $number, 3, '0', STR_PAD_LEFT), range(1, 28)),
            $scenarioCodes,
        );

        foreach ($scenarioCodes as $scenarioCode) {
            $fixture = FbrDemoScenarioFixtures::fixtureByCode($scenarioCode);
            $this->assertNotNull($fixture, "Missing fixture for {$scenarioCode}");

            $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
                ->postJson('/api/mock/fbr/di_data/v1/di/validateinvoicedata_sb', $this->payloadFromFixture($fixture));

            $response->assertOk()
                ->assertJsonPath('validationResponse.status', 'Valid')
                ->assertJsonPath('validationResponse.invoiceStatuses.0.statusCode', '00');
        }
    }

    #[Test]
    public function mock_validate_endpoint_rejects_registered_only_scenarios_for_unregistered_buyers(): void
    {
        config()->set('fbr.security_token', 'mock-fbr-token');

        $payload = $this->validPayload([
            'scenarioId' => 'SN001',
            'buyerRegistrationType' => 'Unregistered',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer mock-fbr-token')
            ->postJson('/api/mock/fbr/di_data/v1/di/validateinvoicedata_sb', $payload);

        $response->assertOk()
            ->assertJsonPath('validationResponse.status', 'Invalid')
            ->assertJsonPath('validationResponse.invoiceStatuses.0.errorCode', '0205');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'invoiceType' => 'Sale Invoice',
            'invoiceDate' => '2025-04-21',
            'sellerNTNCNIC' => '1234567',
            'sellerBusinessName' => 'Company 8',
            'sellerProvince' => 'Sindh',
            'sellerAddress' => 'Karachi',
            'buyerNTNCNIC' => '1234567890123',
            'buyerBusinessName' => 'Buyer',
            'buyerProvince' => 'Sindh',
            'buyerAddress' => 'Karachi',
            'buyerRegistrationType' => 'Registered',
            'invoiceRefNo' => '',
            'scenarioId' => 'SN001',
            'items' => [[
                'hsCode' => '0101.2100',
                'productDescription' => 'product Description',
                'rate' => '18%',
                'uoM' => 'Numbers, pieces, units',
                'quantity' => 1.0000,
                'totalValues' => 1180.00,
                'valueSalesExcludingST' => 1000.00,
                'fixedNotifiedValueOrRetailPrice' => 0.00,
                'salesTaxApplicable' => 180.00,
                'salesTaxWithheldAtSource' => 0.00,
                'extraTax' => 0.00,
                'furtherTax' => 0.00,
                'sroScheduleNo' => '',
                'fedPayable' => 0.00,
                'discount' => 0.00,
                'saleType' => 'Goods at standard rate (default)',
                'sroItemSerialNo' => '',
            ]],
        ], $overrides);
    }

    private function payloadFromFixture(array $fixture): array
    {
        $taxAmount = round($fixture['unit_price'] - ($fixture['unit_price'] / (1 + ($fixture['rate_percent'] / 100))), 2);
        $valueExcludingSalesTax = round($fixture['unit_price'] - $taxAmount, 2);

        $item = array_replace([
            'hsCode' => $fixture['hs_code'],
            'productDescription' => $fixture['description'],
            'rate' => $fixture['rate_label'],
            'uoM' => $fixture['uom'],
            'quantity' => (float) $fixture['quantity'],
            'totalValues' => (float) $fixture['unit_price'],
            'valueSalesExcludingST' => $valueExcludingSalesTax,
            'fixedNotifiedValueOrRetailPrice' => (float) $fixture['fixed_notified_value'],
            'salesTaxApplicable' => $taxAmount,
            'salesTaxWithheldAtSource' => 0.00,
            'extraTax' => 0.00,
            'furtherTax' => 0.00,
            'sroScheduleNo' => $fixture['sro_schedule_number'],
            'fedPayable' => 0.00,
            'discount' => 0.00,
            'saleType' => $fixture['sale_type'],
            'sroItemSerialNo' => $fixture['item_serial_number'],
        ], [
            'valueSalesExcludingST' => $fixture['payload_overrides']['value_excluding_sales_tax'] ?? $valueExcludingSalesTax,
            'salesTaxApplicable' => $fixture['payload_overrides']['sales_tax'] ?? $taxAmount,
            'totalValues' => $fixture['payload_overrides']['total_value'] ?? (float) $fixture['unit_price'],
        ]);

        return [
            'invoiceType' => 'Sale Invoice',
            'invoiceDate' => '2025-04-21',
            'sellerNTNCNIC' => '1234567',
            'sellerBusinessName' => 'Company 8',
            'sellerProvince' => 'Sindh',
            'sellerAddress' => 'Karachi',
            'buyerNTNCNIC' => $fixture['buyer_ntn_cnic'],
            'buyerBusinessName' => $fixture['buyer_name'],
            'buyerProvince' => 'Sindh',
            'buyerAddress' => $fixture['buyer_address'],
            'buyerRegistrationType' => ucfirst($fixture['buyer_type']),
            'invoiceRefNo' => '',
            'scenarioId' => $fixture['scenario_code'],
            'items' => [$item],
        ];
    }
}
