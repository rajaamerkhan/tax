<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HsCodeImportRequest;
use App\Imports\HsCodeImportSheet;
use App\Models\HsCode;
use App\Models\HsCodeImport;
use App\Models\Uom;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReferenceDataController extends Controller
{
    public function index(): View
    {
        return view('reference-data.index', [
            'hsCodes' => HsCode::query()->with('uom')->latest()->paginate(20),
            'uoms' => Uom::query()->orderBy('name')->get(),
            'imports' => HsCodeImport::query()->latest()->limit(10)->get(),
        ]);
    }

    public function importHsCodes(HsCodeImportRequest $request): RedirectResponse
    {
        $batch = HsCodeImport::create([
            'filename' => $request->file('file')->getClientOriginalName(),
            'created_by' => $request->user()->id,
        ]);

        $import = new HsCodeImportSheet();
        Excel::import($import, $request->file('file'));

        $batch->update([
            'status' => empty($import->errors) ? 'imported' : 'completed_with_errors',
            'imported_count' => $import->imported,
            'errors' => $import->errors,
        ]);

        return back()->with('status', 'HS code import completed.');
    }

    public function downloadHsTemplate(): StreamedResponse
    {
        $csv = implode("\n", [
            'hs_code,description,uom,custom_duty_code',
            '2523.2910,PORTLAND CEMENT,KG,CD-001',
        ]);

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, 'hs-code-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
