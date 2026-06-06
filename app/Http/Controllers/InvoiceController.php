<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Jobs\SubmitInvoiceToFbrJob;
use App\Jobs\ValidateInvoiceWithFbrJob;
use App\Models\Customer;
use App\Models\HsCode;
use App\Models\Invoice;
use App\Models\Province;
use App\Models\SaleType;
use App\Models\Scenario;
use App\Models\SroSchedule;
use App\Models\TaxRate;
use App\Services\InvoiceQrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($inner) use ($request): void {
                    $inner->where('invoice_number', 'like', '%'.$request->q.'%')
                        ->orWhere('buyer_name', 'like', '%'.$request->q.'%')
                        ->orWhere('buyer_ntn_cnic', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('lock_state'), function ($query) use ($request): void {
                if ($request->lock_state === 'locked') {
                    $query->whereNotNull('locked_at');
                }

                if ($request->lock_state === 'editable') {
                    $query->whereNull('locked_at')->whereNotNull('editable_until')->where('editable_until', '>', now());
                }
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('invoice_date', '<=', $request->date_to))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $invoiceDate = now()->toDateString();

        return view('invoices.create', $this->formData(new Invoice([
            'invoice_date' => $invoiceDate,
            'invoice_type' => 'Sale Invoice',
            'invoice_number' => $this->generateInvoiceNumber($invoiceDate),
        ])));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = Invoice::create(array_merge($request->safe()->except('items'), [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        $this->syncItems($invoice, $request->validated('items'));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'customer', 'saleOriginProvince', 'destinationProvince', 'scenario']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        abort_if($invoice->isLocked(), 422, 'Locked invoices cannot be edited.');

        $invoice->load('items');

        return view('invoices.edit', $this->formData($invoice));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isLocked(), 422, 'Locked invoices cannot be edited.');

        $invoice->update(array_merge($request->safe()->except('items'), [
            'updated_by' => $request->user()->id,
        ]));

        $invoice->items()->delete();
        $this->syncItems($invoice, $request->validated('items'));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated successfully.');
    }

    public function validateWithFbr(Invoice $invoice): RedirectResponse
    {
        ValidateInvoiceWithFbrJob::dispatch($invoice->id);

        return back()->with('status', 'FBR validation queued.');
    }

    public function submitToFbr(Invoice $invoice): RedirectResponse
    {
        SubmitInvoiceToFbrJob::dispatch($invoice->id);

        return back()->with('status', 'FBR submission queued.');
    }

    public function print(Invoice $invoice): View
    {
        $invoice->load(['items', 'customer', 'saleOriginProvince', 'destinationProvince']);

        $qrCodeService = app(InvoiceQrCodeService::class);

        return view('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeDataUri' => $qrCodeService->dataUri($invoice),
            'verificationUrl' => $qrCodeService->verificationUrl($invoice),
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['items', 'customer', 'saleOriginProvince', 'destinationProvince']);

        $qrCodeService = app(InvoiceQrCodeService::class);
        $pdfBinary = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeDataUri' => $qrCodeService->dataUri($invoice),
            'verificationUrl' => $qrCodeService->verificationUrl($invoice),
        ])
            ->setPaper('a4')
            ->output();

        if ($invoice->pdf_path) {
            Storage::disk('public')->put($invoice->pdf_path, $pdfBinary);
        }

        return response()->streamDownload(function () use ($pdfBinary): void {
            echo $pdfBinary;
        }, 'invoice-'.$invoice->invoice_number.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            $hsCode = HsCode::find($item['hs_code_id'] ?? null);
            $saleType = SaleType::find($item['sale_type_id'] ?? null);
            $sroSchedule = SroSchedule::find($item['sro_schedule_id'] ?? null);

            $invoice->items()->create(array_merge($item, [
                'hs_code' => $item['hs_code'] ?? $hsCode?->code,
                'uom' => $item['uom'] ?? null,
                'uom_id' => null,
                'sale_type' => $item['sale_type'] ?? $saleType?->name ?? $saleType?->code,
                'sro_schedule_number' => $item['sro_schedule_number'] ?? $sroSchedule?->name ?? $sroSchedule?->code,
            ]));
        }

        $invoice->refresh()->load('items');
        $invoice->recalculateTotals();
    }

    private function formData(Invoice $invoice): array
    {
        $oldItems = collect(old('items', []));
        $itemSource = $invoice->relationLoaded('items') ? $invoice->items : $invoice->items()->get();

        $hsCodes = HsCode::with('uom')
            ->whereIn('id', $oldItems->pluck('hs_code_id')->filter()->merge($itemSource->pluck('hs_code_id')->filter())->unique())
            ->orderBy('code')
            ->get();

        $taxRates = TaxRate::query()
            ->where('is_active', true)
            ->whereIn('name', ['18%', '17%', '16%', '15%', '5%', '1%', '0%', 'Exempt'])
            ->orderByRaw("CASE name
                WHEN '18%' THEN 1
                WHEN '17%' THEN 2
                WHEN '16%' THEN 3
                WHEN '15%' THEN 4
                WHEN '5%' THEN 5
                WHEN '1%' THEN 6
                WHEN '0%' THEN 7
                WHEN 'Exempt' THEN 8
                ELSE 99
            END")
            ->get();

        $saleTypes = SaleType::query()
            ->orderBy('name')
            ->get();

        $sroSchedules = SroSchedule::whereIn('id', $oldItems->pluck('sro_schedule_id')->filter()->merge($itemSource->pluck('sro_schedule_id')->filter())->unique())
            ->orderBy('name')
            ->get();

        $defaultCustomerId = Customer::query()->orderBy('name')->value('id');
        $selectedCustomerId = old('customer_id', $invoice->customer_id ?: $defaultCustomerId);
        $selectedCustomer = $selectedCustomerId ? Customer::find($selectedCustomerId) : null;
        $selectedScenarioId = old('scenario_id', $invoice->scenario_id);
        $selectedOriginProvinceId = old('sale_origin_province_id', $invoice->sale_origin_province_id ?: $selectedCustomer?->province_id);
        $selectedDestinationProvinceId = old('destination_province_id', $invoice->destination_province_id ?: $selectedCustomer?->province_id);

        return [
            'invoice' => $invoice,
            'selectedCustomer' => $selectedCustomer,
            'selectedScenario' => $selectedScenarioId ? Scenario::find($selectedScenarioId) : null,
            'invoiceScenarioOptions' => $this->invoiceScenarioOptions(),
            'selectedOriginProvince' => $selectedOriginProvinceId ? Province::find($selectedOriginProvinceId) : null,
            'selectedDestinationProvince' => $selectedDestinationProvinceId ? Province::find($selectedDestinationProvinceId) : null,
            'invoiceProvinceOptions' => $this->invoiceProvinceOptions(),
            'invoiceDestinationOptions' => $this->invoiceDestinationOptions(),
            'uomOptions' => config('invoice.uoms', []),
            'hsCodes' => $hsCodes,
            'taxRates' => $taxRates,
            'saleTypes' => $saleTypes,
            'sroSchedules' => $sroSchedules,
        ];
    }

    private function invoiceScenarioOptions(): Collection
    {
        $scenarioLookup = Scenario::query()
            ->whereIn('code', [
                'SN001', 'SN002', 'SN003', 'SN004', 'SN005', 'SN006', 'SN007', 'SN008', 'SN009',
                'SN010', 'SN011', 'SN012', 'SN013', 'SN014', 'SN015', 'SN016', 'SN017', 'SN018',
                'SN019', 'SN020', 'SN021', 'SN022', 'SN023', 'SN024', 'SN025', 'SN026', 'SN027', 'SN028',
            ])
            ->get()
            ->keyBy('code');

        return collect([
            'SN001', 'SN002', 'SN003', 'SN004', 'SN005', 'SN006', 'SN007', 'SN008', 'SN009',
            'SN010', 'SN011', 'SN012', 'SN013', 'SN014', 'SN015', 'SN016', 'SN017', 'SN018',
            'SN019', 'SN020', 'SN021', 'SN022', 'SN023', 'SN024', 'SN025', 'SN026', 'SN027', 'SN028',
        ])->map(fn (string $code): ?Scenario => $scenarioLookup->get($code))
            ->filter()
            ->values();
    }

    private function invoiceProvinceOptions(): Collection
    {
        $provinceLookup = Province::query()
            ->whereIn('name', [
                'Sindh',
                'Punjab',
                'Khyber Pakhtunkhwa',
                'Balochistan',
                'Islamabad Capital Territory',
                'Gilgit-Baltistan',
                'Azad Jammu and Kashmir',
            ])
            ->get()
            ->keyBy('name');

        return collect([
            ['lookup' => 'Sindh', 'label' => 'Sindh'],
            ['lookup' => 'Punjab', 'label' => 'Punjab'],
            ['lookup' => 'Khyber Pakhtunkhwa', 'label' => 'Khyber Pakhtunkhwa'],
            ['lookup' => 'Balochistan', 'label' => 'Balochistan'],
            ['lookup' => 'Islamabad Capital Territory', 'label' => 'Islamabad Capital Territory'],
            ['lookup' => 'Gilgit-Baltistan', 'label' => 'Gilgit-Baltistan'],
            ['lookup' => 'Azad Jammu and Kashmir', 'label' => 'Azad Jammu & Kashmir'],
        ])->map(function (array $province) use ($provinceLookup): ?array {
            $model = $provinceLookup->get($province['lookup']);

            if (! $model) {
                return null;
            }

            return [
                'id' => $model->id,
                'label' => $province['label'],
            ];
        })->filter()->values();
    }

    private function invoiceDestinationOptions(): Collection
    {
        $provinceLookup = Province::query()
            ->whereIn('name', [
                'Sindh',
                'Punjab',
                'Khyber Pakhtunkhwa',
                'Balochistan',
                'Islamabad Capital Territory',
                'Gilgit-Baltistan',
                'Azad Jammu and Kashmir',
                'Export (Outside Pakistan)',
            ])
            ->get()
            ->keyBy('name');

        return collect([
            ['lookup' => 'Sindh', 'label' => 'Sindh'],
            ['lookup' => 'Punjab', 'label' => 'Punjab'],
            ['lookup' => 'Khyber Pakhtunkhwa', 'label' => 'Khyber Pakhtunkhwa'],
            ['lookup' => 'Balochistan', 'label' => 'Balochistan'],
            ['lookup' => 'Islamabad Capital Territory', 'label' => 'Islamabad Capital Territory'],
            ['lookup' => 'Gilgit-Baltistan', 'label' => 'Gilgit-Baltistan'],
            ['lookup' => 'Azad Jammu and Kashmir', 'label' => 'Azad Jammu & Kashmir'],
            ['lookup' => 'Export (Outside Pakistan)', 'label' => 'Export (Outside Pakistan)'],
        ])->map(function (array $province) use ($provinceLookup): ?array {
            $model = $provinceLookup->get($province['lookup']);

            if (! $model) {
                return null;
            }

            return [
                'id' => $model->id,
                'label' => $province['label'],
            ];
        })->filter()->values();
    }

    private function generateInvoiceNumber(string $invoiceDate): string
    {
        $datePart = Str::of($invoiceDate)->replace('-', '')->value();

        $latestNumber = Invoice::query()
            ->whereDate('invoice_date', $invoiceDate)
            ->where('invoice_number', 'like', 'INV-'.$datePart.'-%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextCounter = 1;

        if ($latestNumber && preg_match('/-(\d{4})$/', $latestNumber, $matches) === 1) {
            $nextCounter = ((int) $matches[1]) + 1;
        }

        return sprintf('INV-%s-%04d', $datePart, $nextCounter);
    }
}
