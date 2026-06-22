<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyProfileRequest;
use App\Models\Client;
use App\Jobs\SyncFbrReferenceDataJob;
use App\Models\CompanyProfile;
use App\Models\Province;
use App\Support\FbrSandboxProfile;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function edit(): View
    {
        $clientId = $this->tenantContext->clientId(auth()->user());

        return view('company.edit', $this->formData(
            CompanyProfile::firstOrNew(['client_id' => $clientId]),
            route('company.update'),
            route('company.sync-references'),
        ));
    }

    public function editClient(Client $client): View
    {
        return view('company.edit', $this->formData(
            CompanyProfile::firstOrNew(['client_id' => $client->id]),
            route('owner.clients.company.update', $client),
            null,
            $client->name,
        ));
    }

    public function update(CompanyProfileRequest $request): RedirectResponse
    {
        $this->saveProfile($request, $this->tenantContext->clientId($request->user()));

        return back()->with('status', 'Company profile updated successfully.');
    }

    public function updateClient(CompanyProfileRequest $request, Client $client): RedirectResponse
    {
        $this->saveProfile($request, $client->id);

        return back()->with('status', $client->name.' company profile updated successfully.');
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

    private function formData(CompanyProfile $company, string $updateRoute, ?string $syncRoute, ?string $clientName = null): array
    {
        return [
            'company' => $company,
            'provinces' => $this->provinceOptions(),
            'businessNatures' => FbrSandboxProfile::businessNatures(),
            'updateRoute' => $updateRoute,
            'syncRoute' => $syncRoute,
            'clientName' => $clientName,
        ];
    }

    private function saveProfile(CompanyProfileRequest $request, ?int $clientId): void
    {
        $data = $request->validated();

        foreach (['fbr_token', 'fbr_sandbox_token', 'fbr_production_token'] as $tokenField) {
            if (! $request->filled($tokenField)) {
                unset($data[$tokenField]);
            }
        }

        CompanyProfile::query()->updateOrCreate(
            ['client_id' => $clientId],
            array_merge($data, ['client_id' => $clientId]),
        );
    }
}
