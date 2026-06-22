<?php

namespace Tests\Unit;

use App\Support\InvoiceCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_invoice_item_totals(): void
    {
        $calculator = app(InvoiceCalculator::class);

        $item = $calculator->calculateItem([
            'quantity' => 1,
            'unit_price' => 100,
            'rate_percent' => 18,
            'discount' => 20,
            'extra_tax' => 20,
            'further_tax' => 10,
            'fed_payable' => 5,
        ]);

        $this->assertSame(82.0, $item['value_excluding_sales_tax']);
        $this->assertSame(18.0, $item['sales_tax']);
        $this->assertSame(133.0, $item['total_value']);
    }

    #[Test]
    public function fixed_notified_value_is_only_used_as_third_schedule_line_basis(): void
    {
        $calculator = app(InvoiceCalculator::class);

        $item = $calculator->calculateItem([
            'quantity' => 41000,
            'unit_price' => 22.247683,
            'fixed_notified_value' => 1038966.67,
            'rate_percent' => 18,
            'discount' => 0,
            'extra_tax' => 0,
            'further_tax' => 0,
            'fed_payable' => 0,
            'sale_type' => '3rd Schedule Goods',
        ]);

        $this->assertSame(912155.0, $item['value_excluding_sales_tax']);
        $this->assertSame(187014.0, $item['sales_tax']);
        $this->assertSame(1225980.67, $item['total_value']);
    }

    #[Test]
    public function duplicate_item_edits_recalculate_independently(): void
    {
        $calculator = app(InvoiceCalculator::class);

        $original = $calculator->calculateItem([
            'quantity' => 1,
            'unit_price' => 100,
            'rate_percent' => 18,
            'discount' => 0,
            'extra_tax' => 20,
            'further_tax' => 20,
            'fed_payable' => 20,
        ]);

        $duplicateEdited = $calculator->calculateItem([
            'quantity' => 1,
            'unit_price' => 200,
            'rate_percent' => 18,
            'discount' => 0,
            'extra_tax' => 20,
            'further_tax' => 20,
            'fed_payable' => 20,
        ]);

        $this->assertSame(18.0, $original['sales_tax']);
        $this->assertSame(36.0, $duplicateEdited['sales_tax']);
        $this->assertSame(178.0, $original['total_value']);
        $this->assertSame(296.0, $duplicateEdited['total_value']);
    }

    #[Test]
    public function it_summarizes_invoice_items(): void
    {
        $calculator = app(InvoiceCalculator::class);

        $summary = $calculator->summarize([
            ['value_excluding_sales_tax' => 84.75, 'sales_tax' => 15.25, 'extra_tax' => 20, 'further_tax' => 10, 'fed_payable' => 5, 'discount' => 20, 'total_value' => 115],
            ['value_excluding_sales_tax' => 169.49, 'sales_tax' => 30.51, 'extra_tax' => 20, 'further_tax' => 20, 'fed_payable' => 20, 'discount' => 0, 'total_value' => 260],
        ]);

        $this->assertSame(254.24, $summary['value_excluding_sales_tax']);
        $this->assertSame(45.76, $summary['sales_tax_amount']);
        $this->assertSame(40.0, $summary['extra_tax_amount']);
        $this->assertSame(30.0, $summary['further_tax_amount']);
        $this->assertSame(25.0, $summary['fed_amount']);
        $this->assertSame(20.0, $summary['discount_amount']);
        $this->assertSame(375.0, $summary['grand_total']);
    }
}
