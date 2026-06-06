<?php

namespace Database\Seeders;

use App\Models\SaleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SaleTypeSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Goods at standard rate',
            'Goods at Reduced Rate',
            'Goods at zero-rate',
            'Exempt goods',
            'Goods as per SRO',
            'Services',
            'Services (FED in ST Mode)',
            'Electricity Supply to Retailers',
            'Special procedure goods',
            'Re-rollable scrap',
            'Cell phone activation',
            'Non-adjustable supplies',
            '3rd Schedule Goods',
            'Mobile Phones',
            'Processing/Conversion of Goods',
            'Toll Manufacturing',
            'Steel melting and re-rolling',
            'Ship breaking',
            'Cotton ginners',
            'Petroleum Products',
            'Gas to CNG stations',
            'Goods (FED in ST Mode)',
            'Electric Vehicle',
            'Cement /Concrete Block',
            'Potassium Chlorate',
            'CNG Sales',
            'Goods as per SRO.297(I)/2023',
            'Telecommunication services',
        ])->values()->each(function (string $name, int $index): void {
            SaleType::updateOrCreate(
                ['code' => sprintf('ST%03d', $index + 1)],
                ['name' => $name, 'fbr_id' => (string) Str::padLeft((string) ($index + 1), 3, '0')],
            );
        });
    }
}
