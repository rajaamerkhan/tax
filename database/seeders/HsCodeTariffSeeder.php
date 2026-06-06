<?php

namespace Database\Seeders;

use App\Models\HsCode;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\LazyCollection;

class HsCodeTariffSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/hs-code-import-template-populated.csv');

        if (! is_file($path)) {
            $this->command?->warn("HS tariff seed file not found: {$path}");

            return;
        }

        $uomMap = Uom::query()
            ->get(['id', 'code', 'name'])
            ->flatMap(fn (Uom $uom) => [
                strtoupper((string) $uom->code) => $uom->id,
                strtoupper((string) $uom->name) => $uom->id,
            ])
            ->filter()
            ->all();

        $rows = LazyCollection::make(function () use ($path) {
            $handle = fopen($path, 'r');

            if ($handle === false) {
                return;
            }

            $header = fgetcsv($handle);
            if (! is_array($header)) {
                fclose($handle);

                return;
            }

            $header = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $header,
            );

            while (($data = fgetcsv($handle)) !== false) {
                if ($data === [null] || $data === false) {
                    continue;
                }

                yield array_combine($header, $data);
            }

            fclose($handle);
        });

        $timestamp = now();

        $rows
            ->filter(fn (array $row) => filled($row['hs_code'] ?? null) && filled($row['description'] ?? null))
            ->map(function (array $row) use ($uomMap, $timestamp): array {
                $uomCode = strtoupper(trim((string) ($row['uom'] ?? '')));

                return [
                    'code' => $this->normalizeHsCode($row['hs_code'] ?? ''),
                    'description' => trim((string) ($row['description'] ?? '')),
                    'uom_id' => $uomCode !== '' ? ($uomMap[$uomCode] ?? null) : null,
                    'custom_duty_code' => $this->nullableString($row['custom_duty_code'] ?? null),
                    'is_active' => true,
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ];
            })
            ->chunk(500)
            ->each(function (LazyCollection $chunk): void {
                HsCode::query()->upsert(
                    $chunk->values()->all(),
                    ['code'],
                    ['description', 'uom_id', 'custom_duty_code', 'is_active', 'updated_at'],
                );
            });

        $this->command?->info('HS tariff seed imported: '.HsCode::query()->count().' rows.');
    }

    private function normalizeHsCode(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        return trim((string) $value);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '0';
    }
}
