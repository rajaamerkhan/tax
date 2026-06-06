<?php

namespace App\Imports;

use App\Models\HsCode;
use App\Models\Uom;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HsCodeImportSheet implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rawCode = $row['hs_code'] ?? $row['code'] ?? '';
            $code = $this->normalizeHsCode($rawCode);
            $description = trim((string) ($row['description'] ?? ''));
            $uomCode = trim((string) ($row['uom'] ?? $row['uom_code'] ?? ''));
            $customDutyCode = trim((string) ($row['custom_duty_code'] ?? $row['duty_code'] ?? ''));

            if ($code === '' || $description === '') {
                $this->errors[$index + 2] = 'HS code and description are required.';
                continue;
            }

            $uomId = null;
            if ($uomCode !== '') {
                $uom = Uom::query()->where('code', $uomCode)->orWhere('name', $uomCode)->first();
                $uomId = $uom?->id;
            }

            HsCode::updateOrCreate(
                ['code' => $code],
                [
                    'description' => $description,
                    'uom_id' => $uomId,
                    'custom_duty_code' => $customDutyCode !== '' ? $customDutyCode : '0',
                    'is_active' => true,
                ],
            );

            $this->imported++;
        }
    }

    private function normalizeHsCode(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        return trim((string) $value);
    }
}
