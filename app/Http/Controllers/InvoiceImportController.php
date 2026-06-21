<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceImportRequest;
use App\Imports\InvoiceDraftImport;
use App\Models\Invoice;
use App\Models\InvoiceImportBatch;
use App\Support\FbrEnvironmentContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

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
            $invoice = Invoice::create([
                'client_id' => $import->client_id,
                'invoice_number' => $invoiceNumber ?: 'IMP-'.Str::upper(Str::random(8)),
                'invoice_date' => Carbon::parse($first['invoice_date'])->toDateString(),
                'invoice_type' => $first['invoice_type'] ?: 'Sale Invoice',
                'environment' => $this->environmentContext->current(),
                'buyer_name' => $first['buyer_name'],
                'buyer_ntn_cnic' => $first['buyer_ntn_cnic'],
                'buyer_strn' => $first['buyer_strn'],
                'status' => 'draft',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($rows as $row) {
                $invoice->items()->create([
                    'description' => $row['item_description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'rate_percent' => $row['rate_percent'],
                    'hs_code' => $row['hs_code'],
                    'uom' => $row['uom'],
                ]);
            }

            $count++;
        }

        $import->update(['imported_count' => $count, 'status' => 'imported']);

        return redirect()->route('imports.show', $import)->with('status', 'Draft invoices imported successfully.');
    }

    public function sampleTemplate()
    {
        $csv = implode("\n", [
            'invoice_number,invoice_date,buyer_name,buyer_ntn_cnic,buyer_strn,invoice_type,description,quantity,unit_price,rate_percent,hs_code,uom',
            'INV-1001,2026-06-05,ABC Traders,1111111-7,12345-6,Sale Invoice,OPC Cement,18,13342.39,18,2523.29,KG',
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fbr-invoice-import-template.csv"',
        ]);
    }
}
