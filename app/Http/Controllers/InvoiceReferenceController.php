<?php

namespace App\Http\Controllers;

use App\Models\HsCode;
use App\Models\SaleType;
use App\Models\SroSchedule;
use App\Models\TaxRate;
use App\Services\FbrDigitalInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceReferenceController extends Controller
{
    public function __invoke(Request $request, FbrDigitalInvoiceService $service): JsonResponse
    {
        $hsCode = HsCode::query()->find($request->integer('hs_code_id'));
        $saleType = SaleType::query()->find($request->integer('sale_type_id'));

        $rates = collect();
        if ($saleType && $request->filled('invoice_date') && $request->filled('sale_origin_province_id')) {
            $rateRows = collect($service->fetchReferenceData('rate_ids', [
                'date' => $request->input('invoice_date'),
                'transTypeId' => $saleType->fbr_id ?: $saleType->id,
                'originationSupplier' => $request->input('sale_origin_province_id'),
            ]));

            $rateValues = $rateRows->pluck('ratE_VALUE')->map(fn ($value) => (float) $value)->all();
            $rates = TaxRate::query()->when($rateValues !== [], fn ($query) => $query->whereIn('rate', $rateValues))->where('is_active', true)->orderBy('rate')->get();
        }

        $sroRows = collect();
        if ($request->filled('invoice_date')) {
            $sroRows = collect($service->fetchReferenceData('sro_schedule', [
                'rate_id' => $rates->first()?->fbr_id ?: 413,
                'date' => $request->input('invoice_date'),
                'origination_supplier_csv' => $request->input('sale_origin_province_id', 1),
            ]));
        }
        $sroIds = $sroRows->pluck('srO_ID')->filter()->map(fn ($id) => (string) $id)->all();
        $sroSchedules = SroSchedule::query()->when($sroIds !== [], fn ($query) => $query->whereIn('fbr_id', $sroIds))->orderBy('name')->get();

        return response()->json([
            'uoms' => [],
            'rates' => $rates->map(fn (TaxRate $rate) => ['id' => $rate->id, 'label' => $rate->name, 'rate' => $rate->rate]),
            'sroSchedules' => $sroSchedules->map(fn (SroSchedule $sro) => ['id' => $sro->id, 'label' => $sro->name]),
        ]);
    }
}
