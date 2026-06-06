<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CANONICALS = [
        'PB' => ['name' => 'Punjab', 'fbr_code' => '01', 'aliases' => ['PUNJAB']],
        'SD' => ['name' => 'Sindh', 'fbr_code' => '02', 'aliases' => ['SINDH']],
        'KP' => ['name' => 'Khyber Pakhtunkhwa', 'fbr_code' => '03', 'aliases' => ['KHYBER PAKHTUNKHWA']],
        'BL' => ['name' => 'Balochistan', 'fbr_code' => '04', 'aliases' => ['BALOCHISTAN']],
        'ICT' => ['name' => 'Islamabad Capital Territory', 'fbr_code' => '05', 'aliases' => ['ISLAMABAD']],
        'AJK' => ['name' => 'Azad Jammu and Kashmir', 'fbr_code' => '06', 'aliases' => ['AZAD JAMMU & KASHMIR']],
        'GB' => ['name' => 'Gilgit-Baltistan', 'fbr_code' => '07', 'aliases' => []],
        'EXP' => ['name' => 'Export (Outside Pakistan)', 'fbr_code' => '08', 'aliases' => []],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::CANONICALS as $code => $province) {
                $canonicalId = DB::table('provinces')
                    ->where('code', $code)
                    ->value('id');

                if (! $canonicalId) {
                    $canonicalId = DB::table('provinces')->insertGetId([
                        'code' => $code,
                        'name' => $province['name'],
                        'fbr_code' => $province['fbr_code'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('provinces')
                        ->where('id', $canonicalId)
                        ->update([
                            'name' => $province['name'],
                            'fbr_code' => $province['fbr_code'],
                            'updated_at' => now(),
                        ]);
                }

                $duplicateIds = DB::table('provinces')
                    ->where('id', '!=', $canonicalId)
                    ->where(function ($query) use ($province, $code): void {
                        $query->where('code', $code)
                            ->orWhere('name', $province['name']);

                        foreach ($province['aliases'] as $alias) {
                            $query->orWhere('name', $alias)->orWhere('code', $alias);
                        }
                    })
                    ->pluck('id');

                foreach ($duplicateIds as $duplicateId) {
                    DB::table('company_profiles')->where('province_id', $duplicateId)->update(['province_id' => $canonicalId]);
                    DB::table('customers')->where('province_id', $duplicateId)->update(['province_id' => $canonicalId]);
                    DB::table('invoices')->where('sale_origin_province_id', $duplicateId)->update(['sale_origin_province_id' => $canonicalId]);
                    DB::table('invoices')->where('destination_province_id', $duplicateId)->update(['destination_province_id' => $canonicalId]);
                }

                if ($duplicateIds->isNotEmpty()) {
                    DB::table('provinces')->whereIn('id', $duplicateIds)->delete();
                }
            }
        });

        if (! $this->indexExists('provinces', 'provinces_name_unique')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->unique('name', 'provinces_name_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('provinces', 'provinces_name_unique')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->dropUnique('provinces_name_unique');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
