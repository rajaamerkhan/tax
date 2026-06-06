<?php

namespace Database\Seeders;

use App\Models\Scenario;
use Illuminate\Database\Seeder;

class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['code' => 'SN001', 'name' => 'Goods at standard rate to registered buyers'],
            ['code' => 'SN002', 'name' => 'Goods at standard rate to unregistered buyers'],
            ['code' => 'SN003', 'name' => 'Sale of Steel (Melted and Re-Rolled)'],
            ['code' => 'SN004', 'name' => 'Sale by Ship Breakers'],
            ['code' => 'SN005', 'name' => 'Reduced rate sale'],
            ['code' => 'SN006', 'name' => 'Exempt goods sale'],
            ['code' => 'SN007', 'name' => 'Zero rated sale'],
            ['code' => 'SN008', 'name' => 'Sale of 3rd schedule goods'],
            ['code' => 'SN009', 'name' => 'Cotton Spinners purchase from Cotton Ginners (Textile Sector)'],
            ['code' => 'SN010', 'name' => 'Telecom services rendered or provided'],
            ['code' => 'SN011', 'name' => 'Toll Manufacturing sale by Steel sector'],
            ['code' => 'SN012', 'name' => 'Sale of Petroleum products'],
            ['code' => 'SN013', 'name' => 'Electricity Supply to Retailers'],
            ['code' => 'SN014', 'name' => 'Sale of Gas to CNG stations'],
            ['code' => 'SN015', 'name' => 'Sale of mobile phones'],
            ['code' => 'SN016', 'name' => 'Processing / Conversion of Goods'],
            ['code' => 'SN017', 'name' => 'Sale of Goods where FED is charged in ST mode'],
            ['code' => 'SN018', 'name' => 'Services rendered or provided where FED is charged in ST mode'],
            ['code' => 'SN019', 'name' => 'Services rendered or provided'],
            ['code' => 'SN020', 'name' => 'Sale of Electric Vehicles'],
            ['code' => 'SN021', 'name' => 'Sale of Cement /Concrete Block'],
            ['code' => 'SN022', 'name' => 'Sale of Potassium Chlorate'],
            ['code' => 'SN023', 'name' => 'Sale of CNG'],
            ['code' => 'SN024', 'name' => 'Goods sold that are listed in SRO 297(1)/2023'],
            ['code' => 'SN025', 'name' => 'Drugs sold at fixed ST rate under serial 81 of Eighth Schedule Table 1'],
            ['code' => 'SN026', 'name' => 'Sale to End Consumer by retailers'],
            ['code' => 'SN027', 'name' => 'Sale to End Consumer by retailers'],
            ['code' => 'SN028', 'name' => 'Sale to End Consumer by retailers'],
        ])->each(function (array $scenario): void {
            Scenario::updateOrCreate(
                ['code' => $scenario['code']],
                ['name' => $scenario['name'], 'document_type_id' => '1'],
            );
        });
    }
}
