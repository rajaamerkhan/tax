<?php

namespace App\Support;

class FbrSandboxProfile
{
    public static function businessNatures(): array
    {
        return [
            'manufacturer' => 'Manufacturer',
            'importer' => 'Importer',
            'exporter' => 'Exporter',
            'distributor' => 'Distributor',
            'wholesaler' => 'Wholesaler',
            'retailer' => 'Retailer',
            'service_provider' => 'Service Provider',
            'other' => 'Other',
        ];
    }

    public static function sectors(): array
    {
        return [
            'all_other_sectors' => 'All Other Sectors',
            'wholesale_retails' => 'Wholesale / Retails',
            'cement_concrete_blocks' => 'Cement or Concrete Blocks',
            'mobile' => 'Mobile',
            'pharmaceuticals' => 'Pharmaceuticals',
            'cng_stations' => 'CNG Stations',
            'automobile' => 'Automobile',
            'services' => 'Services',
            'gas_distribution' => 'Gas Distribution',
            'electricity_distribution' => 'Electricity Distribution',
            'petroleum' => 'Petroleum',
            'telecom' => 'Telecom',
            'textile' => 'Textile',
            'fmcg' => 'FMCG',
            'steel' => 'Steel',
            'potassium_chlorate' => 'Potassium Chlorate',
        ];
    }

    public static function allowedScenarios(?string $sector): array
    {
        return self::rules($sector)['scenarios'] ?? [];
    }

    public static function allowedSaleTypes(?string $sector): array
    {
        return self::rules($sector)['sale_types'] ?? [];
    }

    public static function allowedScenariosForBusinessNature(?string $businessNature): array
    {
        return self::allowedValuesForBusinessNature($businessNature, 'scenarios');
    }

    public static function allowedSaleTypesForBusinessNature(?string $businessNature): array
    {
        return self::allowedValuesForBusinessNature($businessNature, 'sale_types');
    }

    private static function allowedValuesForBusinessNature(?string $businessNature, string $key): array
    {
        if ($businessNature !== 'distributor') {
            return [];
        }

        return array_values(array_unique(array_merge(...array_map(
            fn (array $rule): array => $rule[$key],
            array_values(self::rules()),
        ))));
    }

    private static function rules(?string $sector = null): array
    {
        $rules = [
            'all_other_sectors' => [
                'scenarios' => ['SN001', 'SN002', 'SN005', 'SN006', 'SN007', 'SN015', 'SN016', 'SN017', 'SN021', 'SN022', 'SN024', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => [
                    'Goods at standard rate',
                    'Goods at standard rate (default)',
                    'Goods at Reduced Rate',
                    'Exempt Goods',
                    'Goods at zero-rate',
                    'Mobile Phones',
                    'Cell phone activation',
                    'Processing/Conversion of Goods',
                    'Goods (FED in ST Mode)',
                    'Cement /Concrete Block',
                    'Potassium Chlorate',
                    'Goods as per SRO.297(|)/2023',
                    '3rd Schedule Goods',
                ],
            ],
            'wholesale_retails' => [
                'scenarios' => ['SN001', 'SN002', 'SN005', 'SN006', 'SN007', 'SN015', 'SN016', 'SN017', 'SN021', 'SN022', 'SN024', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => [
                    'Goods at standard rate',
                    'Goods at standard rate (default)',
                    'Goods at Reduced Rate',
                    'Exempt Goods',
                    'Goods at zero-rate',
                    'Mobile Phones',
                    'Cell phone activation',
                    'Processing/Conversion of Goods',
                    'Goods (FED in ST Mode)',
                    'Cement /Concrete Block',
                    'Potassium Chlorate',
                    'Goods as per SRO.297(|)/2023',
                    '3rd Schedule Goods',
                ],
            ],
            'cement_concrete_blocks' => [
                'scenarios' => ['SN021', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Cement /Concrete Block'],
            ],
            'mobile' => [
                'scenarios' => ['SN015', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Mobile Phones', 'Cell phone activation'],
            ],
            'pharmaceuticals' => [
                'scenarios' => ['SN025', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Non-Adjustable Supplies'],
            ],
            'cng_stations' => [
                'scenarios' => ['SN023', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['CNG Sales'],
            ],
            'automobile' => [
                'scenarios' => ['SN020', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Electric Vehicle'],
            ],
            'services' => [
                'scenarios' => ['SN018', 'SN019', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Services', 'Services (FED in ST Mode)'],
            ],
            'gas_distribution' => [
                'scenarios' => ['SN014', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Gas to CNG stations'],
            ],
            'electricity_distribution' => [
                'scenarios' => ['SN013', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Electricity Supply to Retailers'],
            ],
            'petroleum' => [
                'scenarios' => ['SN012', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Petroleum Products'],
            ],
            'telecom' => [
                'scenarios' => ['SN010', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Telecommunication services'],
            ],
            'textile' => [
                'scenarios' => ['SN009', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Cotton Ginners'],
            ],
            'fmcg' => [
                'scenarios' => ['SN008', 'SN026', 'SN027', 'SN028'],
                'sale_types' => ['3rd Schedule Goods'],
            ],
            'steel' => [
                'scenarios' => ['SN003', 'SN004', 'SN011', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Steel Melting and re-rolling', 'Ship breaking', 'Toll Manufacturing'],
            ],
            'potassium_chlorate' => [
                'scenarios' => ['SN022', 'SN026', 'SN027', 'SN028', 'SN008'],
                'sale_types' => ['Potassium Chlorate'],
            ],
        ];

        if ($sector === null) {
            return $rules;
        }

        return $rules[$sector] ?? [];
    }
}
