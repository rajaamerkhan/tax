<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\FbrApiLog;
use App\Support\FbrDemoScenarioFixtures;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MockFbrConsoleController extends Controller
{
    public function __invoke(Request $request): View
    {
        $logs = FbrApiLog::query()
            ->with('invoice')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('endpoint'), fn ($query) => $query->where('endpoint', 'like', '%'.$request->endpoint.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $company = CompanyProfile::query()->first();
        $demoScenarioOptions = FbrDemoScenarioFixtures::optionsFor($company);

        return view('admin.mock-fbr-console', compact('logs', 'company', 'demoScenarioOptions'));
    }
}
