<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthly = Invoice::query()
            ->whereDate('invoice_date', '>=', now()->startOfMonth()->subMonths(11))
            ->orderBy('invoice_date')
            ->get();

        $monthlyGrouped = $monthly
            ->groupBy(fn (Invoice $invoice) => Carbon::parse($invoice->invoice_date)->format('Y-m'))
            ->map(fn ($items, $month) => [
                'month' => $month,
                'revenue' => $items->sum('grand_total'),
                'invoice_count' => $items->count(),
            ])
            ->values();

        $submissionAttempts = Invoice::query()->whereNotNull('fbr_submitted_at')->count();
        $submissionSuccess = Invoice::query()->whereIn('status', [InvoiceStatus::Submitted->value, InvoiceStatus::Editable->value, InvoiceStatus::Locked->value])->count();

        $topCustomers = Customer::query()
            ->leftJoin('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->select('customers.name')
            ->selectRaw('COUNT(invoices.id) as invoice_count')
            ->selectRaw('COALESCE(SUM(invoices.grand_total),0) as total_revenue')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'totalInvoices' => Invoice::count(),
            'totalTaxAmount' => Invoice::all()->sum(fn (Invoice $invoice) => $invoice->sales_tax_amount + $invoice->extra_tax_amount + $invoice->further_tax_amount + $invoice->fed_amount),
            'pendingFbrSubmissions' => Invoice::whereIn('status', [InvoiceStatus::Validated->value, InvoiceStatus::Failed->value])->count(),
            'totalCustomers' => Customer::count(),
            'monthlyLabels' => $monthlyGrouped->pluck('month'),
            'monthlyRevenue' => $monthlyGrouped->pluck('revenue'),
            'monthlyInvoiceCount' => $monthlyGrouped->pluck('invoice_count'),
            'submissionSuccessRate' => $submissionAttempts > 0 ? round(($submissionSuccess / $submissionAttempts) * 100, 2) : 0,
            'recentInvoices' => Invoice::latest()->limit(8)->get(),
            'topCustomers' => $topCustomers,
        ]);
    }
}
