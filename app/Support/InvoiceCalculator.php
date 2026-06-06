<?php

namespace App\Support;

class InvoiceCalculator
{
    public function calculateItem(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $ratePercent = (float) ($item['rate_percent'] ?? 0);
        $discount = (float) ($item['discount'] ?? 0);
        $fixedNotifiedValueInput = $item['fixed_notified_value'] ?? null;
        $fixedNotifiedValue = $fixedNotifiedValueInput !== null && $fixedNotifiedValueInput !== ''
            ? (float) $fixedNotifiedValueInput
            : null;

        $grossValue = max($quantity * $unitPrice, 0);
        $discount = min($discount, $grossValue);

        $taxBasisUnitPrice = $fixedNotifiedValue !== null && $fixedNotifiedValue > 0 ? $fixedNotifiedValue : $unitPrice;
        $taxBasisGrossValue = max($quantity * $taxBasisUnitPrice, 0);

        $salesTax = $ratePercent > 0
            ? $taxBasisGrossValue - ($taxBasisGrossValue / (1 + ($ratePercent / 100)))
            : 0.0;
        $valueExcludingSalesTax = max($grossValue - $salesTax, 0);
        $extraTax = (float) ($item['extra_tax'] ?? 0);
        $furtherTax = (float) ($item['further_tax'] ?? 0);
        $fedPayable = (float) ($item['fed_payable'] ?? 0);
        $totalValue = max($grossValue + $extraTax + $furtherTax + $fedPayable - $discount, 0);

        return [
            'value_excluding_sales_tax' => round($valueExcludingSalesTax, 2),
            'sales_tax' => round($salesTax, 2),
            'extra_tax' => round($extraTax, 2),
            'further_tax' => round($furtherTax, 2),
            'fed_payable' => round($fedPayable, 2),
            'discount' => round($discount, 2),
            'total_value' => round($totalValue, 2),
        ];
    }

    public function summarize(iterable $items): array
    {
        $summary = [
            'value_excluding_sales_tax' => 0,
            'sales_tax_amount' => 0,
            'extra_tax_amount' => 0,
            'further_tax_amount' => 0,
            'fed_amount' => 0,
            'discount_amount' => 0,
            'grand_total' => 0,
        ];

        foreach ($items as $item) {
            $summary['value_excluding_sales_tax'] += (float) $item['value_excluding_sales_tax'];
            $summary['sales_tax_amount'] += (float) $item['sales_tax'];
            $summary['extra_tax_amount'] += (float) $item['extra_tax'];
            $summary['further_tax_amount'] += (float) $item['further_tax'];
            $summary['fed_amount'] += (float) $item['fed_payable'];
            $summary['discount_amount'] += (float) $item['discount'];
            $summary['grand_total'] += (float) $item['total_value'];
        }

        return array_map(fn ($value) => round($value, 2), $summary);
    }
}
