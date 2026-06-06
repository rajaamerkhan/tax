<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\HsCode;
use App\Models\Invoice;
use App\Models\Province;
use App\Models\SaleType;
use App\Models\Scenario;
use App\Models\TaxRate;
use App\Models\Uom;
use App\Services\FbrDigitalInvoiceService;
use App\Services\InvoiceSubmissionFinalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DemoInvoiceController extends Controller
{
    public function __invoke(FbrDigitalInvoiceService $service, InvoiceSubmissionFinalizer $finalizer): RedirectResponse
    {
        $company = CompanyProfile::query()->firstOrFail();
        $scenario = Scenario::query()->first();
        $originProvince = Province::query()->find($company->province_id) ?? Province::query()->first();
        $destinationProvince = Province::query()->whereKeyNot($originProvince?->id)->first() ?? $originProvince;
        $customer = Customer::query()->first() ?? Customer::query()->create([
            'name' => 'Mock Buyer',
            'ntn_cnic' => '1234567890123',
            'strn' => '1234567890123',
            'buyer_type' => 'registered',
            'province_id' => $destinationProvince?->id,
            'address' => 'Karachi',
            'status' => 'active',
        ]);

        $hsCode = HsCode::query()->with('uom')->first();
        $uom = $hsCode?->uom ?? Uom::query()->first();
        $taxRate = TaxRate::query()->where('is_active', true)->first();
        $saleType = SaleType::query()->first();

        $invoice = Invoice::create([
            'invoice_number' => 'MOCK-'.Str::upper(Str::random(8)),
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'scenario_id' => $scenario?->id,
            'sale_origin_province_id' => $originProvince?->id,
            'destination_province_id' => $destinationProvince?->id,
            'customer_id' => $customer->id,
            'buyer_name' => $customer->name,
            'buyer_ntn_cnic' => $customer->ntn_cnic,
            'buyer_strn' => $customer->strn,
            'buyer_address' => $customer->address,
            'status' => InvoiceStatus::Draft,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $invoice->items()->create([
            'hs_code_id' => $hsCode?->id,
            'uom_id' => $uom?->id,
            'tax_rate_id' => $taxRate?->id,
            'sale_type_id' => $saleType?->id,
            'hs_code' => $hsCode?->code ?? '0101.2100',
            'description' => $hsCode?->description ?? 'Mock product Description',
            'uom' => $uom?->name ?? 'Numbers, pieces, units',
            'quantity' => 1,
            'unit_price' => 1000,
            'rate_percent' => $taxRate?->rate ?? 18,
            'discount' => 0,
            'extra_tax' => 0,
            'further_tax' => 0,
            'fed_payable' => 0,
            'fixed_notified_value' => 0,
            'sale_type' => $saleType?->name ?? 'Goods at standard rate (default)',
            'sro_schedule_number' => '',
            'item_serial_number' => '',
        ]);
        $invoice->refresh()->load('items');
        $invoice->recalculateTotals();

        $validateResponse = $service->validateInvoice($invoice);
        $invoice->update([
            'status' => InvoiceStatus::Validated,
            'fbr_response_json' => $validateResponse,
            'error_message' => null,
        ]);

        $submitResponse = $service->submitInvoice($invoice);
        $finalizer->finalize($invoice, $submitResponse);

        return redirect()->route('admin.mock-fbr-console')->with('status', "Demo invoice {$invoice->invoice_number} validated and submitted to mock FBR.");
    }
}
