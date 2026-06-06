<?php

namespace App\Services;

use App\Models\Province;
use App\Models\SaleType;
use App\Models\Scenario;
use App\Models\SroSchedule;
use App\Models\TaxRate;
use App\Models\Uom;
use Illuminate\Support\Arr;

class ReferenceDataSyncService
{
    public function __construct(private readonly FbrDigitalInvoiceService $fbrService)
    {
    }

    public function sync(): void
    {
        $this->syncProvinces();
        $this->syncUoms();
        $this->syncTaxRates();
        $this->syncSaleTypes();
        $this->syncScenarios();
        $this->syncSroSchedules();
    }

    public function syncProvinces(): void
    {
        $rows = Arr::wrap($this->fbrService->fetchReferenceData('province_codes'));

        foreach ($rows as $row) {
            Province::updateOrCreate(
                ['fbr_code' => (string) Arr::get($row, 'stateProvinceCode', Arr::get($row, 'provinceId', Arr::get($row, 'id')))],
                [
                    'code' => (string) Arr::get($row, 'stateProvinceDesc', Arr::get($row, 'provinceCode', Arr::get($row, 'code', uniqid('PR-')))),
                    'name' => (string) Arr::get($row, 'stateProvinceDesc', Arr::get($row, 'provinceName', Arr::get($row, 'name', 'Unknown Province'))),
                ],
            );
        }
    }

    public function syncUoms(): void
    {
        foreach (Arr::wrap($this->fbrService->fetchReferenceData('uom_ids')) as $row) {
            $code = (string) Arr::get($row, 'description', Arr::get($row, 'uomCode', Arr::get($row, 'code', uniqid('UOM-'))));

            $uom = Uom::query()
                ->where('fbr_id', (string) Arr::get($row, 'uoM_ID', Arr::get($row, 'uomId', Arr::get($row, 'id'))))
                ->orWhere('code', $code)
                ->firstOrNew();

            $uom->fill([
                'fbr_id' => (string) Arr::get($row, 'uoM_ID', Arr::get($row, 'uomId', Arr::get($row, 'id'))),
                'code' => $code,
                'name' => (string) Arr::get($row, 'description', Arr::get($row, 'uomDescription', Arr::get($row, 'name', 'Unknown UOM'))),
            ])->save();
        }
    }

    public function syncTaxRates(): void
    {
        foreach (Arr::wrap($this->fbrService->fetchReferenceData('rate_ids')) as $row) {
            TaxRate::updateOrCreate(
                ['fbr_id' => (string) Arr::get($row, 'ratE_ID', Arr::get($row, 'rateId', Arr::get($row, 'id')))],
                ['name' => (string) Arr::get($row, 'ratE_DESC', Arr::get($row, 'rateDescription', Arr::get($row, 'name', 'Tax Rate'))), 'rate' => (float) Arr::get($row, 'ratE_VALUE', Arr::get($row, 'rate', 0)), 'is_active' => true],
            );
        }
    }

    public function syncSaleTypes(): void
    {
        foreach (Arr::wrap($this->fbrService->fetchReferenceData('transaction_type_ids')) as $row) {
            SaleType::updateOrCreate(
                ['fbr_id' => (string) Arr::get($row, 'transactioN_TYPE_ID', Arr::get($row, 'transactionTypeId', Arr::get($row, 'id')))],
                ['code' => (string) Arr::get($row, 'transactioN_DESC', Arr::get($row, 'transactionTypeCode', Arr::get($row, 'code', uniqid('SALE-')))), 'name' => (string) Arr::get($row, 'transactioN_DESC', Arr::get($row, 'transactionTypeDescription', Arr::get($row, 'name', 'Sale Type')))],
            );
        }
    }

    public function syncScenarios(): void
    {
        foreach (Arr::wrap($this->fbrService->fetchReferenceData('document_types')) as $row) {
            Scenario::updateOrCreate(
                ['code' => (string) Arr::get($row, 'docDescription', Arr::get($row, 'documentTypeCode', Arr::get($row, 'code', uniqid('SCN-'))))],
                ['name' => (string) Arr::get($row, 'docDescription', Arr::get($row, 'documentTypeDescription', Arr::get($row, 'name', 'Scenario'))), 'document_type_id' => (string) Arr::get($row, 'docTypeId', Arr::get($row, 'documentTypeId', Arr::get($row, 'id')))],
            );
        }
    }

    public function syncSroSchedules(): void
    {
        foreach (Arr::wrap($this->fbrService->fetchReferenceData('sro_schedule')) as $row) {
            SroSchedule::updateOrCreate(
                ['fbr_id' => (string) Arr::get($row, 'srO_ID', Arr::get($row, 'scheduleId', Arr::get($row, 'id')))],
                ['code' => (string) Arr::get($row, 'srO_DESC', Arr::get($row, 'scheduleCode', Arr::get($row, 'code', uniqid('SRO-')))), 'name' => (string) Arr::get($row, 'srO_DESC', Arr::get($row, 'scheduleDescription', Arr::get($row, 'name', 'SRO Schedule')))],
            );
        }
    }
}
