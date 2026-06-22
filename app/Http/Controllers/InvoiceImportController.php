<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceImportRequest;
use App\Imports\InvoiceDraftImport;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceImportBatch;
use App\Models\InvoiceItem;
use App\Models\Province;
use App\Models\Scenario;
use App\Support\FbrEnvironmentContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InvoiceImportController extends Controller
{
    public function __construct(
        private readonly FbrEnvironmentContext $environmentContext,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        return view('imports.index', [
            'batches' => InvoiceImportBatch::query()->where('client_id', $this->tenantContext->clientId(auth()->user()))->latest()->limit(10)->get(),
        ]);
    }

    public function preview(InvoiceImportRequest $request): RedirectResponse
    {
        $import = new InvoiceDraftImport;
        Excel::import($import, $request->file('file'));

        $rows = $import->rows ?? collect();
        $errors = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, [
                'invoice_number' => ['required'],
                'invoice_date' => ['required'],
                'buyer_name' => ['required'],
                'item_description' => ['required'],
                'quantity' => ['required', 'numeric'],
                'unit_price' => ['required', 'numeric'],
                'rate_percent' => ['required', 'numeric'],
            ]);

            if ($validator->fails()) {
                $errors[$index + 2] = $validator->errors()->all();
            }
        }

        $batch = InvoiceImportBatch::create([
            'client_id' => $this->tenantContext->clientId($request->user()),
            'filename' => $request->file('file')->getClientOriginalName(),
            'preview_rows' => $rows->take(200)->values()->all(),
            'errors' => $errors,
            'status' => empty($errors) ? 'previewed' : 'has_errors',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('imports.show', $batch)->with('status', 'Import preview generated.');
    }

    public function show(InvoiceImportBatch $import): View
    {
        $this->tenantContext->authorizeModel($import);

        return view('imports.show', ['batch' => $import]);
    }

    public function store(InvoiceImportBatch $import): RedirectResponse
    {
        $this->tenantContext->authorizeModel($import);
        abort_if(! empty($import->errors), 422, 'Fix import errors before importing.');

        $grouped = collect($import->preview_rows)->groupBy('invoice_number');
        $count = 0;

        foreach ($grouped as $invoiceNumber => $rows) {
            $first = $rows->first();
            $customer = $this->resolveCustomer($first, $import->client_id);
            $companyProfile = CompanyProfile::query()->where('client_id', $import->client_id)->first();
            $invoice = Invoice::create([
                'client_id' => $import->client_id,
                'invoice_number' => $invoiceNumber ?: 'IMP-'.Str::upper(Str::random(8)),
                'invoice_date' => Carbon::parse($first['invoice_date'])->toDateString(),
                'invoice_type' => $first['invoice_type'] ?: 'Sale Invoice',
                'environment' => $this->environmentContext->current($import->client_id),
                'scenario_id' => $this->resolveScenarioId($first['scenario_code'] ?? null),
                'sale_origin_province_id' => $companyProfile?->province_id,
                'destination_province_id' => $customer?->province_id ?: $this->resolveProvinceId($first['destination_province'] ?? null),
                'customer_id' => $customer?->id,
                'buyer_name' => $first['buyer_name'],
                'buyer_ntn_cnic' => $first['buyer_ntn_cnic'],
                'buyer_strn' => $first['buyer_strn'],
                'buyer_address' => $first['buyer_address'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($rows as $row) {
                $item = [
                    'description' => $row['item_description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'rate_percent' => $row['rate_percent'],
                    'hs_code' => $row['hs_code'],
                    'uom' => $row['uom'],
                    'sale_type' => $row['sale_type'] ?? null,
                    'fixed_notified_value' => $row['fixed_notified_value'] ?? null,
                    'extra_tax' => $row['extra_tax'] ?? 0,
                    'further_tax' => $row['further_tax'] ?? 0,
                    'fed_payable' => $row['fed_payable'] ?? 0,
                    'discount' => $row['discount'] ?? 0,
                    'sro_schedule_number' => $row['sro_schedule_number'] ?? null,
                    'item_serial_number' => $row['item_serial_number'] ?? null,
                ];

                if ($row['has_explicit_values'] ?? false) {
                    InvoiceItem::withoutEvents(function () use ($invoice, $item, $row): void {
                        $invoice->items()->create(array_merge($item, [
                            'value_excluding_sales_tax' => $row['value_excluding_sales_tax'] ?? 0,
                            'sales_tax' => $row['sales_tax'] ?? 0,
                            'total_value' => $row['total_value']
                                ?? ((float) ($row['value_excluding_sales_tax'] ?? 0)
                                    + (float) ($row['sales_tax'] ?? 0)
                                    + (float) ($row['extra_tax'] ?? 0)
                                    + (float) ($row['further_tax'] ?? 0)
                                    + (float) ($row['fed_payable'] ?? 0)
                                    - (float) ($row['discount'] ?? 0)),
                        ]));
                    });
                } else {
                    $invoice->items()->create($item);
                }
            }

            $invoice->refresh()->load('items');
            $invoice->recalculateTotals();
            $count++;
        }

        $import->update(['imported_count' => $count, 'status' => 'imported']);

        return redirect()->route('imports.show', $import)->with('status', 'Draft invoices imported successfully.');
    }

    public function sampleTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');
        $sheet->fromArray([
            [
                'Sr.',
                'Buyer NTN/CNIC',
                'Buyer Name',
                'Buyer Type',
                'Buyer Address',
                'Destination of Supply',
                'Document Type',
                'Document Number',
                'Document Date',
                'Scenario',
                'Sale Type',
                'Rate',
                'Hs Code',
                'Description',
                'Quantity',
                'Unit Price',
                'UOM',
                'Value of Sales Excluding Sales Tax',
                'Fixed / notified value or Retail Price',
                'Sales Tax/ FED in ST Mode',
                'Extra Tax',
                'ST Withheld at Source',
                'SRO No. / Schedule No.',
                'Item Sr. No.',
                'Further Tax',
                'Discount',
                'Total Value of Sales',
            ],
            [
                1,
                '9999999999999',
                'FBR INTERNAL',
                'Unregistered',
                'Lahore',
                'PUNJAB',
                'Sale Invoice',
                '797',
                '01-May-2026',
                'SN008',
                ' 3rd Schedule Goods ',
                '18%',
                '2523.2900:-',
                'CEMENT',
                41000,
                null,
                'KG',
                912155,
                1038966.6666666666,
                187014,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
        ]);

        $sheet->getStyle('A1:AA1')->getFont()->setBold(true);

        for ($columnIndex = 1; $columnIndex <= 27; $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'Sales Format May-2026 Digital Invoice.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveCustomer(array $row, ?int $clientId): Customer
    {
        $query = Customer::query()->where('client_id', $clientId);
        $ntnCnic = $row['buyer_ntn_cnic'] ?? null;

        $customer = $ntnCnic
            ? (clone $query)->where('ntn_cnic', $ntnCnic)->first()
            : null;

        $customer ??= (clone $query)
            ->where('name', $row['buyer_name'])
            ->when($row['buyer_address'] ?? null, fn (Builder $query, string $address) => $query->where('address', $address))
            ->first();

        if ($customer) {
            return $customer;
        }

        return Customer::create([
            'client_id' => $clientId,
            'name' => $row['buyer_name'],
            'ntn_cnic' => $ntnCnic,
            'strn' => $row['buyer_strn'] ?? null,
            'buyer_type' => $row['buyer_type'] ?? 'unregistered',
            'province_id' => $this->resolveProvinceId($row['destination_province'] ?? null),
            'address' => $row['buyer_address'] ?? null,
            'status' => 'active',
        ]);
    }

    private function resolveProvinceId(?string $province): ?int
    {
        if (! $province) {
            return null;
        }

        $province = trim($province);

        return Province::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($province)])
            ->orWhereRaw('LOWER(code) = ?', [strtolower($province)])
            ->value('id');
    }

    private function resolveScenarioId(?string $scenario): ?int
    {
        if (! $scenario) {
            return null;
        }

        return Scenario::query()
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($scenario))])
            ->value('id');
    }
}
