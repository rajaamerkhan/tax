<?php

namespace App\Imports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InvoiceDraftImport implements ToCollection, WithHeadingRow
{
    public Collection $rows;

    public function collection(Collection $collection): void
    {
        $this->rows = $collection
            ->filter(fn ($row) => $row->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
            ->map(fn ($row) => $this->mapRow($row))
            ->values();
    }

    private function mapRow(Collection $row): array
    {
        $quantity = $this->number($this->value($row, ['quantity']));
        $unitPrice = $this->number($this->value($row, ['unit_price', 'price']));
        $valueExcludingSalesTax = $this->number($this->value($row, [
            'value_of_sales_excluding_sales_tax',
            'value_sales_excluding_st',
            'value_excluding_sales_tax',
        ]));
        $salesTax = $this->number($this->value($row, [
            'sales_tax_fed_in_st_mode',
            'sales_tax_applicable',
            'sales_tax',
        ]));
        $fixedNotifiedValue = $this->number($this->value($row, [
            'fixed_notified_value_or_retail_price',
            'fixed_notified_value',
            'retail_price',
        ]));
        $extraTax = $this->number($this->value($row, ['extra_tax']));
        $furtherTax = $this->number($this->value($row, ['further_tax']));
        $fedPayable = $this->number($this->value($row, ['fed_payable', 'fed_in_st_mode']));
        $discount = $this->number($this->value($row, ['discount']));
        $totalValue = $this->number($this->value($row, ['total_value_of_sales', 'total_value', 'total_values']));
        $saleType = $this->text($this->value($row, ['sale_type']));

        if (($unitPrice === null || $unitPrice == 0.0) && $quantity > 0) {
            $derivedUnitValue = $valueExcludingSalesTax ?? $totalValue;

            if ($derivedUnitValue !== null && $derivedUnitValue > 0) {
                $unitPrice = round($derivedUnitValue / $quantity, 6);
            }
        }

        $ratePercent = $this->number($this->value($row, ['rate_percent', 'rate']));

        return [
            'invoice_number' => $this->text($this->value($row, ['invoice_number', 'invoice_no', 'document_number'])),
            'invoice_date' => $this->date($this->value($row, ['invoice_date', 'date', 'document_date'])),
            'buyer_name' => $this->text($this->value($row, ['buyer_name'])),
            'buyer_ntn_cnic' => $this->text($this->value($row, ['buyer_ntn_cnic', 'ntn_cnic', 'buyer_ntncnic'])),
            'buyer_strn' => $this->text($this->value($row, ['buyer_strn', 'strn'])),
            'buyer_type' => $this->buyerType($this->value($row, ['buyer_type'])),
            'buyer_address' => $this->text($this->value($row, ['buyer_address'])),
            'destination_province' => $this->text($this->value($row, ['destination_of_supply', 'destination_province', 'buyer_province'])),
            'invoice_type' => $this->text($this->value($row, ['invoice_type', 'document_type'])) ?: 'Sale Invoice',
            'scenario_code' => $this->text($this->value($row, ['scenario', 'scenario_code', 'scenario_id'])),
            'sale_type' => $saleType,
            'item_description' => $this->text($this->value($row, ['description', 'item_description'])),
            'quantity' => $quantity ?? 0,
            'unit_price' => $unitPrice ?? 0,
            'rate_percent' => $ratePercent ?? 0,
            'hs_code' => $this->hsCode($this->value($row, ['hs_code', 'hs_code_'])),
            'uom' => $this->text($this->value($row, ['uom'])),
            'value_excluding_sales_tax' => $valueExcludingSalesTax,
            'fixed_notified_value' => $fixedNotifiedValue,
            'sales_tax' => $salesTax,
            'extra_tax' => $extraTax,
            'further_tax' => $furtherTax,
            'fed_payable' => $fedPayable,
            'discount' => $discount,
            'sro_schedule_number' => $this->text($this->value($row, ['sro_no_schedule_no', 'sro_schedule_no', 'sro_no'])),
            'item_serial_number' => $this->text($this->value($row, ['item_sr_no', 'sro_item_serial_no'])),
            'total_value' => $this->derivedTotalValue(
                $valueExcludingSalesTax,
                $salesTax,
                $extraTax,
                $furtherTax,
                $fedPayable,
                $discount,
            ) ?? $totalValue,
            'has_explicit_values' => $valueExcludingSalesTax !== null || $salesTax !== null || $totalValue !== null,
        ];
    }

    private function value(Collection $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($row->has($key) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $normalized === '' || ! is_numeric($normalized) ? null : (float) $normalized;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    private function buyerType(mixed $value): string
    {
        return str_contains(strtolower((string) $value), 'registered') && ! str_contains(strtolower((string) $value), 'unregistered')
            ? 'registered'
            : 'unregistered';
    }

    private function hsCode(mixed $value): ?string
    {
        $value = $this->text($value);

        return $value ? rtrim($value, ':- ') : null;
    }

    private function derivedTotalValue(
        ?float $valueExcludingSalesTax,
        ?float $salesTax,
        ?float $extraTax,
        ?float $furtherTax,
        ?float $fedPayable,
        ?float $discount,
    ): ?float {
        if ($valueExcludingSalesTax === null) {
            return null;
        }

        return round(
            $valueExcludingSalesTax
            + (float) $salesTax
            + (float) $extraTax
            + (float) $furtherTax
            + (float) $fedPayable
            - (float) $discount,
            2,
        );
    }
}
