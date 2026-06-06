<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            ['code' => 'Numbers, pieces, units', 'name' => 'Numbers, pieces, units', 'fbr_id' => '91'],
            ['code' => 'KG', 'name' => 'KG', 'fbr_id' => '13'],
            ['code' => 'Kilogram', 'name' => 'Kilogram', 'fbr_id' => null],
            ['code' => 'Gram', 'name' => 'Gram', 'fbr_id' => null],
            ['code' => 'MT', 'name' => 'MT', 'fbr_id' => null],
            ['code' => 'Pound', 'name' => 'Pound', 'fbr_id' => null],
            ['code' => '40KG', 'name' => '40KG', 'fbr_id' => null],
            ['code' => 'Carat', 'name' => 'Carat', 'fbr_id' => null],
            ['code' => 'Liter', 'name' => 'Liter', 'fbr_id' => null],
            ['code' => 'Gallon', 'name' => 'Gallon', 'fbr_id' => null],
            ['code' => 'Barrels', 'name' => 'Barrels', 'fbr_id' => null],
            ['code' => 'Cubic Metre', 'name' => 'Cubic Metre', 'fbr_id' => null],
            ['code' => 'Meter', 'name' => 'Meter', 'fbr_id' => null],
            ['code' => 'Foot', 'name' => 'Foot', 'fbr_id' => null],
            ['code' => 'Square Foot', 'name' => 'Square Foot', 'fbr_id' => null],
            ['code' => 'Square Metre', 'name' => 'Square Metre', 'fbr_id' => '77'],
            ['code' => 'SqY', 'name' => 'SqY', 'fbr_id' => null],
            ['code' => 'KWH', 'name' => 'KWH', 'fbr_id' => null],
            ['code' => '1000 kWh', 'name' => '1000 kWh', 'fbr_id' => null],
            ['code' => 'MMBTU', 'name' => 'MMBTU', 'fbr_id' => null],
            ['code' => 'Mega Watt', 'name' => 'Mega Watt', 'fbr_id' => null],
            ['code' => 'NO', 'name' => 'NO', 'fbr_id' => null],
            ['code' => 'Thousand Unit', 'name' => 'Thousand Unit', 'fbr_id' => null],
            ['code' => 'Dozen', 'name' => 'Dozen', 'fbr_id' => null],
            ['code' => 'Packs', 'name' => 'Packs', 'fbr_id' => null],
            ['code' => 'Bag', 'name' => 'Bag', 'fbr_id' => null],
            ['code' => 'SET', 'name' => 'SET', 'fbr_id' => null],
            ['code' => 'Pair', 'name' => 'Pair', 'fbr_id' => null],
            ['code' => 'Bill of lading', 'name' => 'Bill of lading', 'fbr_id' => null],
            ['code' => 'Timber Logs', 'name' => 'Timber Logs', 'fbr_id' => null],
            ['code' => 'Others', 'name' => 'Others', 'fbr_id' => null],
        ];

        Uom::query()->upsert(
            array_map(fn (array $uom) => $uom + ['created_at' => now(), 'updated_at' => now()], $uoms),
            ['code'],
            ['name', 'fbr_id', 'updated_at'],
        );

        $allowedCodes = array_column($uoms, 'code');

        $referencedUomIds = collect(DB::table('invoice_items')->whereNotNull('uom_id')->pluck('uom_id'))
            ->merge(DB::table('hs_codes')->whereNotNull('uom_id')->pluck('uom_id'))
            ->unique()
            ->all();

        Uom::query()
            ->whereNotIn('code', $allowedCodes)
            ->when($referencedUomIds !== [], fn ($query) => $query->whereNotIn('id', $referencedUomIds))
            ->delete();

        $this->command?->info('UOM master synced: '.Uom::query()->count().' rows.');
    }
}
