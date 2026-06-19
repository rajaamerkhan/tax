<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
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
}
