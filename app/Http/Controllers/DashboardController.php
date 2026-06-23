<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Support\FbrEnvironmentContext;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly FbrEnvironmentContext $environmentContext,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(): View
    {
        abort_if(auth()->user()?->isOwner() && ! $this->tenantContext->isManagingClient(auth()->user()), 403);

        $environment = $this->environmentContext->current();
        $clientId = $this->tenantContext->clientId(auth()->user());
        $client = $this->tenantContext->client(auth()->user());
        $monthly = Invoice::query()
            ->forClient($clientId)
            ->where('environment', $environment)
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

        $submissionAttempts = Invoice::query()->forClient($clientId)->where('environment', $environment)->whereNotNull('fbr_submitted_at')->count();
        $submissionSuccess = Invoice::query()->forClient($clientId)->where('environment', $environment)->whereIn('status', [InvoiceStatus::Submitted->value, InvoiceStatus::Editable->value, InvoiceStatus::Locked->value])->count();
        $totalInvoices = Invoice::forClient($clientId)->where('environment', $environment)->count();
        $totalCustomers = Customer::forClient($clientId)->count();
        $pendingFbrSubmissions = Invoice::forClient($clientId)->where('environment', $environment)->whereIn('status', [InvoiceStatus::Validated->value, InvoiceStatus::Failed->value])->count();
        $monthlySeries = collect(range(11, 0))->map(function (int $monthsAgo) use ($monthlyGrouped): array {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $row = $monthlyGrouped->firstWhere('month', $month->format('Y-m'));

            return [
                'label' => $month->format('M Y'),
                'revenue' => (float) ($row['revenue'] ?? 0),
                'invoice_count' => (int) ($row['invoice_count'] ?? 0),
            ];
        });
        $quotaLimit = (int) ($client?->max_invoices_per_month ?? 30);
        $quotaUsed = $client?->invoiceCountForMonth(environment: $environment) ?? 0;

        $topCustomers = Customer::query()
            ->forClient($clientId)
            ->leftJoin('invoices', function ($join) use ($environment, $clientId): void {
                $join->on('customers.id', '=', 'invoices.customer_id')
                    ->where('invoices.client_id', $clientId)
                    ->where('invoices.environment', $environment)
                    ->whereNull('invoices.deleted_at');
            })
            ->select('customers.name')
            ->selectRaw('COUNT(invoices.id) as invoice_count')
            ->selectRaw('COALESCE(SUM(invoices.grand_total),0) as total_revenue')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'client' => $client,
            'totalInvoices' => $totalInvoices,
            'totalTaxAmount' => Invoice::forClient($clientId)->where('environment', $environment)->get()->sum(fn (Invoice $invoice) => $invoice->sales_tax_amount + $invoice->extra_tax_amount + $invoice->further_tax_amount + $invoice->fed_amount),
            'pendingFbrSubmissions' => $pendingFbrSubmissions,
            'totalCustomers' => $totalCustomers,
            'monthlyLabels' => $monthlySeries->pluck('label'),
            'monthlyRevenue' => $monthlySeries->pluck('revenue'),
            'monthlyInvoiceCount' => $monthlySeries->pluck('invoice_count'),
            'submissionSuccessRate' => $submissionAttempts > 0 ? round(($submissionSuccess / $submissionAttempts) * 100, 2) : 0,
            'submissionSuccess' => $submissionSuccess,
            'submissionAttempts' => $submissionAttempts,
            'quotaLimit' => $quotaLimit,
            'quotaUsed' => $quotaUsed,
            'quotaRemaining' => max($quotaLimit - $quotaUsed, 0),
            'recentInvoices' => Invoice::forClient($clientId)->where('environment', $environment)->latest()->limit(8)->get(),
            'topCustomers' => $topCustomers,
        ]);
    }
}
