<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyProfileRequest;
use App\Jobs\SyncFbrReferenceDataJob;
use App\Models\CompanyProfile;
use App\Models\Province;
use App\Support\FbrSandboxProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        return view('company.edit', [
            'company' => CompanyProfile::firstOrNew(),
            'provinces' => $this->provinceOptions(),
            'businessNatures' => FbrSandboxProfile::businessNatures(),
        ]);
    }

    public function update(CompanyProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! $request->filled('fbr_token')) {
            unset($data['fbr_token']);
        }

        CompanyProfile::query()->updateOrCreate(['id' => CompanyProfile::query()->value('id')], $data);

        return back()->with('status', 'Company profile updated successfully.');
    }

    public function syncReferences(): RedirectResponse
    {
        SyncFbrReferenceDataJob::dispatch();

        return back()->with('status', 'Reference data sync queued.');
    }

    private function provinceOptions(): Collection
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
        ])->map(function (array $province) use ($provinceLookup): ?Province {
            $model = $provinceLookup->get($province['lookup']);

            if (! $model) {
                return null;
            }

            $model->setAttribute('display_name', $province['label']);

            return $model;
        })->filter()->values();
    }
}
