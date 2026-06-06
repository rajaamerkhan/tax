<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::query()
            ->whereNotIn('name', ['18%', '17%', '16%', '15%', '5%', '1%', '0%', 'Exempt'])
            ->update(['is_active' => false]);

        $rates = collect([
            ['code' => 'TR018', 'name' => '18%', 'rate' => 18],
            ['code' => 'TR017', 'name' => '17%', 'rate' => 17],
            ['code' => 'TR016', 'name' => '16%', 'rate' => 16],
            ['code' => 'TR015', 'name' => '15%', 'rate' => 15],
            ['code' => 'TR005', 'name' => '5%', 'rate' => 5],
            ['code' => 'TR001', 'name' => '1%', 'rate' => 1],
            ['code' => 'TR000', 'name' => '0%', 'rate' => 0],
            ['code' => 'TREXM', 'name' => 'Exempt', 'rate' => 0],
        ]);

        $rates->each(function (array $rate): void {
            TaxRate::updateOrCreate(
                ['name' => $rate['name']],
                ['rate' => $rate['rate'], 'fbr_id' => $rate['code'], 'is_active' => true],
            );
        });

        $activeEighteenRateId = TaxRate::query()->where('name', '18%')->value('id');
        $legacyStandardRateId = TaxRate::query()->where('name', 'Standard 18%')->value('id');

        if ($activeEighteenRateId && $legacyStandardRateId && $activeEighteenRateId !== $legacyStandardRateId) {
            DB::table('invoice_items')
                ->where('tax_rate_id', $legacyStandardRateId)
                ->update(['tax_rate_id' => $activeEighteenRateId]);
        }
    }
}
