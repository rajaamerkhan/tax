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

        $this->assertSame(84.75, $item['value_excluding_sales_tax']);
        $this->assertSame(15.25, $item['sales_tax']);
        $this->assertSame(115.0, $item['total_value']);
    }

    #[Test]
    public function fixed_notified_value_overrides_tax_basis_without_changing_total_value(): void
    {
        $calculator = app(InvoiceCalculator::class);

        $item = $calculator->calculateItem([
            'quantity' => 1,
            'unit_price' => 100,
            'fixed_notified_value' => 200,
            'rate_percent' => 18,
            'discount' => 0,
            'extra_tax' => 0,
            'further_tax' => 0,
            'fed_payable' => 0,
        ]);

        $this->assertSame(69.49, $item['value_excluding_sales_tax']);
        $this->assertSame(30.51, $item['sales_tax']);
        $this->assertSame(100.0, $item['total_value']);
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

        $this->assertSame(15.25, $original['sales_tax']);
        $this->assertSame(30.51, $duplicateEdited['sales_tax']);
        $this->assertSame(160.0, $original['total_value']);
        $this->assertSame(260.0, $duplicateEdited['total_value']);
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
