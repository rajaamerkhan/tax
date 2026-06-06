<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FbrApiLog;
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

        return view('admin.mock-fbr-console', compact('logs'));
    }
}
