<?php

namespace App\Support;

use App\Enums\BuyerType;
use App\Models\CompanyProfile;

class FbrDemoScenarioFixtures
{
    public static function optionsFor(?CompanyProfile $company): array
    {
        if ($company?->fbr_business_nature !== 'distributor') {
            return self::options(['SN002']);
        }

        return self::options([
            'SN002', 'SN008', 'SN010', 'SN012', 'SN013', 'SN014',
            'SN016', 'SN017', 'SN018', 'SN019', 'SN026', 'SN027',
        ]);
    }

    public static function fixtureFor(?CompanyProfile $company, ?string $scenarioCode): ?array
    {
        $scenarioCode = $scenarioCode ?: 'SN002';
        $allowed = array_column(self::optionsFor($company), 'code');

        if (! in_array($scenarioCode, $allowed, true)) {
            return null;
        }

        return self::fixtures()[$scenarioCode] ?? null;
    }

    private static function options(array $scenarioCodes): array
    {
        return array_values(array_map(
            fn (string $code): array => ['code' => $code, 'label' => $code.' - '.self::fixtures()[$code]['scenario_name']],
            $scenarioCodes,
        ));
    }

    private static function fixtures(): array
    {
        $standard = [
            'buyer_name' => 'Mock Unregistered Buyer',
            'buyer_ntn_cnic' => '3730128493065',
            'buyer_strn' => '',
            'buyer_type' => BuyerType::Unregistered->value,
            'buyer_address' => 'Karachi',
            'hs_code' => '0101.2100',
            'description' => 'Pure-bred breeding animals',
            'uom' => 'Numbers, pieces, units',
            'rate_label' => '18%',
            'rate_percent' => 18,
            'sale_type_code' => 'DEMO-STANDARD-GOODS',
            'quantity' => 1,
            'unit_price' => 1000,
            'fixed_notified_value' => 0,
            'payload_overrides' => [],
        ];

        return [
            'SN002' => array_replace_recursive($standard, [
                'scenario_code' => 'SN002',
                'scenario_name' => 'Goods at standard rate to unregistered buyers',
                'sale_type' => 'Goods at standard rate (default)',
            ]),
            'SN008' => array_replace_recursive($standard, [
                'scenario_code' => 'SN008',
                'scenario_name' => 'Sale of 3rd schedule goods',
                'sale_type' => '3rd Schedule Goods',
                'sale_type_code' => 'DEMO-3RD-SCHEDULE',
                'fixed_notified_value' => 1000,
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 180,
                    'total_value' => 1180,
                ],
            ]),
            'SN010' => array_replace_recursive($standard, [
                'scenario_code' => 'SN010',
                'scenario_name' => 'Telecom services rendered or provided',
                'sale_type' => 'Telecommunication services',
                'sale_type_code' => 'DEMO-TELECOM',
                'rate_label' => '17%',
                'rate_percent' => 17,
                'unit_price' => 1170,
            ]),
            'SN012' => array_replace_recursive($standard, [
                'scenario_code' => 'SN012',
                'scenario_name' => 'Sale of Petroleum products',
                'sale_type' => 'Petroleum Products',
                'sale_type_code' => 'DEMO-PETROLEUM',
            ]),
            'SN013' => array_replace_recursive($standard, [
                'scenario_code' => 'SN013',
                'scenario_name' => 'Electricity Supply to Retailers',
                'sale_type' => 'Electricity Supply to Retailers',
                'sale_type_code' => 'DEMO-ELECTRICITY',
                'rate_label' => '5%',
                'rate_percent' => 5,
                'unit_price' => 1050,
            ]),
            'SN014' => array_replace_recursive($standard, [
                'scenario_code' => 'SN014',
                'scenario_name' => 'Sale of Gas to CNG stations',
                'sale_type' => 'Gas to CNG stations',
                'sale_type_code' => 'DEMO-GAS-CNG',
            ]),
            'SN016' => array_replace_recursive($standard, [
                'scenario_code' => 'SN016',
                'scenario_name' => 'Processing / Conversion of Goods',
                'sale_type' => 'Processing/Conversion of Goods',
                'sale_type_code' => 'DEMO-PROCESSING',
            ]),
            'SN017' => array_replace_recursive($standard, [
                'scenario_code' => 'SN017',
                'scenario_name' => 'Sale of Goods where FED is charged in ST mode',
                'sale_type' => 'Goods (FED in ST Mode)',
                'sale_type_code' => 'DEMO-GOODS-FED',
                'rate_label' => '17%',
                'rate_percent' => 17,
                'unit_price' => 1170,
            ]),
            'SN018' => array_replace_recursive($standard, [
                'scenario_code' => 'SN018',
                'scenario_name' => 'Services rendered or provided where FED is charged in ST mode',
                'sale_type' => 'Services (FED in ST Mode)',
                'sale_type_code' => 'DEMO-SERVICES-FED',
                'rate_label' => '17%',
                'rate_percent' => 17,
                'unit_price' => 1170,
            ]),
            'SN019' => array_replace_recursive($standard, [
                'scenario_code' => 'SN019',
                'scenario_name' => 'Services rendered or provided',
                'sale_type' => 'Services',
                'sale_type_code' => 'DEMO-SERVICES',
                'rate_label' => 'Exempt',
                'rate_percent' => 0,
            ]),
            'SN026' => array_replace_recursive($standard, [
                'scenario_code' => 'SN026',
                'scenario_name' => 'Sale to End Consumer by retailers',
                'sale_type' => 'Goods at standard rate (default)',
            ]),
            'SN027' => array_replace_recursive($standard, [
                'scenario_code' => 'SN027',
                'scenario_name' => 'Sale to End Consumer by retailers',
                'sale_type' => '3rd Schedule Goods',
                'sale_type_code' => 'DEMO-RETAIL-3RD-SCHEDULE',
                'fixed_notified_value' => 1000,
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 180,
                    'total_value' => 1180,
                ],
            ]),
        ];
    }
}
