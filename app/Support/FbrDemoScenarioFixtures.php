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

        $scenarioCodes = FbrSandboxProfile::allowedScenariosForBusinessNature('distributor');
        sort($scenarioCodes);

        return self::options($scenarioCodes);
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

    public static function fixtureByCode(string $scenarioCode): ?array
    {
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
            ...self::unregisteredBuyer(),
            'hs_code' => '0101.2100',
            'description' => 'Pure-bred breeding animals',
            'uom' => 'Numbers, pieces, units',
            'rate_label' => '18%',
            'rate_percent' => 18,
            'sale_type_code' => 'DEMO-STANDARD-GOODS',
            'quantity' => 1,
            'unit_price' => 1180,
            'fixed_notified_value' => 0,
            'sro_schedule_number' => '',
            'item_serial_number' => '',
            'payload_overrides' => [],
        ];

        return [
            'SN001' => self::scenario($standard, 'SN001', 'Goods at standard rate to registered buyers', 'Goods at standard rate (default)', [
                ...self::registeredBuyer(),
            ]),
            'SN002' => self::scenario($standard, 'SN002', 'Goods at standard rate to unregistered buyers', 'Goods at standard rate (default)', [
                ...self::unregisteredBuyer(),
            ]),
            'SN003' => self::scenario($standard, 'SN003', 'Sale of Steel (Melted and Re-Rolled)', 'Steel Melting and re-rolling', [
                'sale_type_code' => 'DEMO-STEEL-MELTING',
                'hs_code' => '7214.9990',
                'description' => 'Other bars and rods of iron or non-alloy steel',
                'uom' => 'MT',
                'unit_price' => 243280.60,
            ]),
            'SN004' => self::scenario($standard, 'SN004', 'Sale by Ship Breakers', 'Ship breaking', [
                'sale_type_code' => 'DEMO-SHIP-BREAKING',
                'hs_code' => '7204.4910',
                'description' => 'Ship breaking scrap',
                'uom' => 'MT',
                'unit_price' => 243280.60,
            ]),
            'SN005' => self::scenario($standard, 'SN005', 'Reduced rate sale', 'Goods at Reduced Rate', [
                'sale_type_code' => 'DEMO-REDUCED-RATE',
                'hs_code' => '0102.2930',
                'description' => 'Cattle',
                'rate_label' => '10%',
                'rate_percent' => 10,
                'unit_price' => 1100,
                'sro_schedule_number' => 'EIGHTH SCHEDULE Table 1',
                'item_serial_number' => '1',
            ]),
            'SN006' => self::scenario($standard, 'SN006', 'Exempt goods sale', 'Exempt Goods', [
                ...self::registeredBuyer(),
                'sale_type_code' => 'DEMO-EXEMPT-GOODS',
                'hs_code' => '0102.2930',
                'description' => 'Cattle',
                'rate_label' => 'Exempt',
                'rate_percent' => 0,
                'unit_price' => 1000,
                'sro_schedule_number' => 'EIGHTH SCHEDULE Table 1',
                'item_serial_number' => '81',
            ]),
            'SN007' => self::scenario($standard, 'SN007', 'Zero rated sale', 'Goods at zero-rate', [
                'sale_type_code' => 'DEMO-ZERO-RATE',
                'hs_code' => '0102.2930',
                'description' => 'Cattle',
                'rate_label' => '0%',
                'rate_percent' => 0,
                'unit_price' => 1000,
                'sro_schedule_number' => '327(I)/2008',
                'item_serial_number' => '1',
            ]),
            'SN008' => self::scenario($standard, 'SN008', 'Sale of 3rd schedule goods', '3rd Schedule Goods', [
                'sale_type_code' => 'DEMO-3RD-SCHEDULE',
                'fixed_notified_value' => 1000,
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 180,
                    'total_value' => 1180,
                ],
            ]),
            'SN009' => self::scenario($standard, 'SN009', 'Cotton Spinners purchase from Cotton Ginners (Textile Sector)', 'Cotton Ginners', [
                ...self::registeredBuyer(),
                'sale_type_code' => 'DEMO-COTTON-GINNERS',
                'hs_code' => '5201.0090',
                'description' => 'Other cotton, not carded or combed',
                'uom' => 'KG',
            ]),
            'SN010' => self::scenario($standard, 'SN010', 'Telecom services rendered or provided', 'Telecommunication services', [
                'sale_type_code' => 'DEMO-TELECOM',
                'rate_label' => '17%',
                'rate_percent' => 17,
                'unit_price' => 1170,
            ]),
            'SN011' => self::scenario($standard, 'SN011', 'Toll Manufacturing sale by Steel sector', 'Toll Manufacturing', [
                'sale_type_code' => 'DEMO-TOLL-MANUFACTURING',
                'hs_code' => '7207.1100',
                'description' => 'Semi-finished products of iron or non-alloy steel',
            ]),
            'SN012' => self::scenario($standard, 'SN012', 'Sale of Petroleum products', 'Petroleum Products', [
                'sale_type_code' => 'DEMO-PETROLEUM',
                'hs_code' => '2710.1210',
                'description' => 'Motor spirit',
                'uom' => 'Liter',
            ]),
            'SN013' => self::scenario($standard, 'SN013', 'Electricity Supply to Retailers', 'Electricity Supply to Retailers', [
                'sale_type_code' => 'DEMO-ELECTRICITY',
                'hs_code' => '2716.0000',
                'description' => 'Electrical energy',
                'uom' => 'KWH',
                'rate_label' => '5%',
                'rate_percent' => 5,
                'unit_price' => 1050,
            ]),
            'SN014' => self::scenario($standard, 'SN014', 'Sale of Gas to CNG stations', 'Gas to CNG stations', [
                'sale_type_code' => 'DEMO-GAS-CNG',
                'hs_code' => '2711.2100',
                'description' => 'Natural gas in gaseous state',
            ]),
            'SN015' => self::scenario($standard, 'SN015', 'Sale of mobile phones', 'Mobile Phones', [
                'sale_type_code' => 'DEMO-MOBILE-PHONES',
                'hs_code' => '8517.6990',
                'description' => 'Mobile phones',
                'uom' => 'Numbers, pieces, units',
                'rate_label' => '18%',
                'rate_percent' => 18,
                'unit_price' => 1180,
                'sro_schedule_number' => 'NINTH SCHEDULE',
                'item_serial_number' => '1(A)',
            ]),
            'SN016' => self::scenario($standard, 'SN016', 'Processing / Conversion of Goods', 'Processing/ Conversion of Goods', [
                'sale_type_code' => 'DEMO-PROCESSING',
            ]),
            'SN017' => self::scenario($standard, 'SN017', 'Sale of Goods where FED is charged in ST mode', 'Goods (FED in ST Mode)', [
                'sale_type_code' => 'DEMO-GOODS-FED',
                'rate_label' => '17%',
                'rate_percent' => 17,
                'unit_price' => 1170,
            ]),
            'SN018' => self::scenario($standard, 'SN018', 'Services rendered or provided where FED is charged in ST mode', 'Services (FED in ST Mode)', [
                'sale_type_code' => 'DEMO-SERVICES-FED',
                'rate_label' => '17%',
                'rate_percent' => 17,
                'unit_price' => 1170,
            ]),
            'SN019' => self::scenario($standard, 'SN019', 'Services rendered or provided', 'Services', [
                'sale_type_code' => 'DEMO-SERVICES',
                'rate_label' => 'Exempt',
                'rate_percent' => 0,
                'unit_price' => 1000,
            ]),
            'SN020' => self::scenario($standard, 'SN020', 'Sale of Electric Vehicles', 'Electric Vehicle', [
                'sale_type_code' => 'DEMO-ELECTRIC-VEHICLE',
                'hs_code' => '8703.8010',
                'description' => 'Components for the assembly / manufacture of vehicles, in any kit form excluding those of heading 8703.8030',
                'rate_label' => '1%',
                'rate_percent' => 1,
                'unit_price' => 1010,
                'sro_schedule_number' => '6th Schd Table III',
                'item_serial_number' => '20',
            ]),
            'SN021' => self::scenario($standard, 'SN021', 'Sale of Cement /Concrete Block', 'Cement /Concrete Block', [
                'sale_type_code' => 'DEMO-CEMENT-CONCRETE',
                'hs_code' => '6810.1100',
                'description' => 'Building blocks and bricks',
                'uom' => 'KG',
                'rate_label' => 'Rs.2',
                'rate_percent' => 2,
                'unit_price' => 1002,
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 2,
                    'total_value' => 1002,
                ],
            ]),
            'SN022' => self::scenario($standard, 'SN022', 'Sale of Potassium Chlorate', 'Potassium Chlorate', [
                'sale_type_code' => 'DEMO-POTASSIUM-CHLORATE',
                'hs_code' => '2829.1910',
                'description' => 'Potassium chlorate',
                'uom' => 'KG',
                'rate_label' => '18% along with rupees 60 per kilogram',
                'rate_percent' => 18,
                'unit_price' => 1240,
                'sro_schedule_number' => 'EIGHTH SCHEDULE Table 1',
                'item_serial_number' => '56',
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 240,
                    'total_value' => 1240,
                ],
            ]),
            'SN023' => self::scenario($standard, 'SN023', 'Sale of CNG', 'CNG Sales', [
                'sale_type_code' => 'DEMO-CNG-SALES',
                'hs_code' => '2711.2100',
                'description' => 'Natural gas',
                'uom' => 'KG',
                'rate_label' => 'Rs.200',
                'rate_percent' => 200,
                'unit_price' => 1200,
                'sro_schedule_number' => '581(1)/2024',
                'item_serial_number' => 'Region-I',
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 200,
                    'total_value' => 1200,
                ],
            ]),
            'SN024' => self::scenario($standard, 'SN024', 'Goods sold that are listed in SRO 297(1)/2023', 'Goods as per SRO.297(|)/2023', [
                'sale_type_code' => 'DEMO-SRO-297',
                'rate_label' => '25%',
                'rate_percent' => 25,
                'unit_price' => 1250,
                'sro_schedule_number' => '297(I)/2023-Table-I',
                'item_serial_number' => '12',
            ]),
            'SN025' => self::scenario($standard, 'SN025', 'Drugs sold at fixed ST rate under serial 81 of Eighth Schedule Table 1', 'Non-Adjustable Supplies', [
                'sale_type_code' => 'DEMO-NON-ADJUSTABLE',
                'hs_code' => '3004.9099',
                'description' => 'Medicaments',
                'uom' => 'KG',
                'rate_label' => '0%',
                'rate_percent' => 0,
                'unit_price' => 1000,
                'sro_schedule_number' => 'EIGHTH SCHEDULE Table 1',
                'item_serial_number' => '81',
            ]),
            'SN026' => self::scenario($standard, 'SN026', 'Sale to End Consumer by retailers', 'Goods at standard rate (default)', [
                ...self::unregisteredBuyer(),
            ]),
            'SN027' => self::scenario($standard, 'SN027', 'Sale to End Consumer by retailers', '3rd Schedule Goods', [
                ...self::unregisteredBuyer(),
                'sale_type_code' => 'DEMO-RETAIL-3RD-SCHEDULE',
                'fixed_notified_value' => 1000,
                'payload_overrides' => [
                    'value_excluding_sales_tax' => 1000,
                    'sales_tax' => 180,
                    'total_value' => 1180,
                ],
            ]),
            'SN028' => self::scenario($standard, 'SN028', 'Sale to End Consumer by retailers', 'Goods at Reduced Rate', [
                ...self::unregisteredBuyer(),
                'sale_type_code' => 'DEMO-RETAIL-REDUCED-RATE',
                'rate_label' => '5%',
                'rate_percent' => 5,
                'unit_price' => 1050,
                'sro_schedule_number' => 'EIGHTH SCHEDULE Table 1',
                'item_serial_number' => '77',
            ]),
        ];
    }

    private static function scenario(array $standard, string $code, string $name, string $saleType, array $overrides = []): array
    {
        return array_replace_recursive($standard, [
            'scenario_code' => $code,
            'scenario_name' => $name,
            'sale_type' => $saleType,
        ], $overrides);
    }

    private static function registeredBuyer(): array
    {
        return [
            'buyer_name' => 'FERTILIZER MANUFAC IRS NEW',
            'buyer_ntn_cnic' => '2046004',
            'buyer_strn' => '2046004',
            'buyer_type' => BuyerType::Registered->value,
            'buyer_address' => 'Karachi',
        ];
    }

    private static function unregisteredBuyer(): array
    {
        return [
            'buyer_name' => 'Mock Unregistered Buyer',
            'buyer_ntn_cnic' => '3730128493065',
            'buyer_strn' => '',
            'buyer_type' => BuyerType::Unregistered->value,
            'buyer_address' => 'Karachi',
        ];
    }
}
