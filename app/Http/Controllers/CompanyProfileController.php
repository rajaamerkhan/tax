<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyProfileRequest;
use App\Jobs\SyncFbrReferenceDataJob;
use App\Models\CompanyProfile;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        return view('company.edit', [
            'company' => CompanyProfile::firstOrNew(),
            'provinces' => Province::orderBy('name')->get(),
        ]);
    }

    public function update(CompanyProfileRequest $request): RedirectResponse
    {
        CompanyProfile::query()->updateOrCreate(['id' => CompanyProfile::query()->value('id')], $request->validated());

        return back()->with('status', 'Company profile updated successfully.');
    }

    public function syncReferences(): RedirectResponse
    {
        SyncFbrReferenceDataJob::dispatch();

        return back()->with('status', 'Reference data sync queued.');
    }
}
