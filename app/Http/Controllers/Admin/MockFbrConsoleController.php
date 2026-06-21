<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\FbrApiLog;
use App\Support\FbrDemoScenarioFixtures;
use App\Support\FbrEnvironmentContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MockFbrConsoleController extends Controller
{
    public function __construct(
        private readonly FbrEnvironmentContext $environmentContext,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): View
    {
        $clientId = $this->tenantContext->clientId($request->user());
        $logs = FbrApiLog::query()
            ->with('invoice')
            ->where('client_id', $clientId)
            ->where('environment', $this->environmentContext->current())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('endpoint'), fn ($query) => $query->where('endpoint', 'like', '%'.$request->endpoint.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $company = CompanyProfile::query()->where('client_id', $clientId)->first();
        $demoScenarioOptions = FbrDemoScenarioFixtures::optionsFor($company);

        return view('admin.mock-fbr-console', compact('logs', 'company', 'demoScenarioOptions'));
    }
}
