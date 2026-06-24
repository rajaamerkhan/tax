<?php

namespace App\Support;

use App\Models\CompanyProfile;
use App\Models\Invoice;

class InvoicePayloadBuilder
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function build(Invoice $invoice): array
    {
        $invoice->loadMissing(['items', 'customer', 'saleOriginProvince', 'destinationProvince', 'scenario']);
        $company = CompanyProfile::query()
            ->where('client_id', $this->tenantContext->invoiceClientId($invoice))
            ->first();

        return [
            'invoiceType' => $invoice->invoice_type ?: '',
            'invoiceDate' => optional($invoice->invoice_date)->format('Y-m-d') ?: '',
            'sellerNTNCNIC' => $company?->fbr_registration_number ?: ($company?->ntn_cnic ?: ''),
            'sellerBusinessName' => $company?->name ?: '',
            'sellerProvince' => $invoice->saleOriginProvince?->name ?? $invoice->saleOriginProvince?->code ?? '',
            'sellerAddress' => $company?->address ?: '',
            'buyerNTNCNIC' => $invoice->buyer_ntn_cnic ?: $invoice->customer?->ntn_cnic ?: '',
            'buyerBusinessName' => $invoice->buyer_name ?: $invoice->customer?->name ?: '',
            'buyerProvince' => $invoice->destinationProvince?->name ?? $invoice->destinationProvince?->code ?? '',
            'buyerAddress' => $invoice->buyer_address ?: $invoice->customer?->address ?: '',
            'buyerRegistrationType' => ucfirst((string) ($invoice->customer?->buyer_type?->value ?? 'Unregistered')),
            'invoiceRefNo' => '',
            'scenarioId' => $invoice->scenario?->code ?: '',
            'items' => $invoice->items->map(fn ($item) => [
                'hsCode' => $item->hs_code ?: $item->hsCodeRelation?->code ?: '',
                'productDescription' => $item->description ?: '',
                'rate' => match ($item->sale_type) {
                    'Exempt Goods' => 'Exempt',
                    'Cement /Concrete Block' => 'Rs.'.rtrim(rtrim(number_format((float) $item->rate_percent, 2, '.', ''), '0'), '.'),
                    'Potassium Chlorate' => '18% along with rupees 60 per kilogram',
                    'CNG Sales' => 'Rs.'.rtrim(rtrim(number_format((float) $item->rate_percent, 2, '.', ''), '0'), '.'),
                    default => rtrim(rtrim(number_format((float) $item->rate_percent, 2, '.', ''), '0'), '.').'%',
                },
                'uoM' => $item->uom ?: $item->uomRelation?->name ?: $item->uomRelation?->code ?: '',
                'quantity' => (float) $item->quantity,
                'totalValues' => (float) $item->total_value,
                'valueSalesExcludingST' => (float) $item->value_excluding_sales_tax,
                'fixedNotifiedValueOrRetailPrice' => $item->fixed_notified_value !== null ? (float) $item->fixed_notified_value : 0.0,
                'salesTaxApplicable' => (float) $item->sales_tax,
                'salesTaxWithheldAtSource' => 0.0,
                'extraTax' => in_array($item->sale_type, ['Goods at Reduced Rate', 'Exempt Goods', 'Goods at zero-rate'], true) ? '' : (float) $item->extra_tax,
                'furtherTax' => (float) $item->further_tax,
                'sroScheduleNo' => $item->sro_schedule_number ?: '',
                'fedPayable' => (float) $item->fed_payable,
                'discount' => (float) $item->discount,
                'saleType' => $item->sale_type ?: 'Goods at standard rate (default)',
                'sroItemSerialNo' => $item->item_serial_number ?: '',
            ])->values()->all(),
        ];
    }
}
