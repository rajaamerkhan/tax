@extends('layouts.app')
@section('title', 'Seller Dashboard')
@section('content')
@php
    $syncRate = min(max((float) $submissionSuccessRate, 0), 100);
    $quotaRate = $quotaLimit > 0 ? min(($quotaUsed / $quotaLimit) * 100, 100) : 100;
@endphp

<div class="dashboard-command mb-4">
    <div>
        <p>Welcome back, {{ auth()->user()->name }}.</p>
    </div>
    <form method="GET" action="{{ route('invoices.index') }}" class="dashboard-search">
        <i class="bi bi-search"></i>
        <input name="q" placeholder="Search invoices, buyers, NTN..." autocomplete="off">
    </form>
</div>

<div class="dashboard-stat-grid mb-4">
    <div class="dashboard-stat-card accent-blue">
        <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
        <div><span>Total Invoices</span><strong>{{ number_format($totalInvoices) }}</strong></div>
    </div>
    <div class="dashboard-stat-card accent-green">
        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div><span>Tax Amount</span><strong>PKR {{ number_format($totalTaxAmount, 0) }}</strong></div>
    </div>
    <div class="dashboard-stat-card accent-amber">
        <div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div><span>Pending FBR</span><strong>{{ number_format($pendingFbrSubmissions) }}</strong></div>
    </div>
    <div class="dashboard-stat-card accent-red">
        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        <div><span>Total Customers</span><strong>{{ number_format($totalCustomers) }}</strong></div>
    </div>
</div>

<div class="dashboard-layout">
    <div class="dashboard-main">
        <div class="panel dashboard-chart-panel">
            <div class="panel-header">
                <h2>Monthly Revenue & Volume</h2>
                <div class="chart-toggle"><span class="active">12 Months</span><span>Invoices</span></div>
            </div>
            <div class="dashboard-chart-body">
                <canvas id="dashboardChart"></canvas>
            </div>
        </div>

        <div class="panel dashboard-activity-panel">
            <div class="panel-header">
                <h2>Recent Activity</h2>
                <a href="{{ route('invoices.index') }}">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle dashboard-activity-table">
                    <thead><tr><th>Invoice #</th><th>Customer</th><th>FBR Status</th><th class="text-end">Total Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentInvoices as $invoice)
                        <tr>
                            <td>
                                <a class="activity-invoice" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                <div class="text-secondary small">{{ $invoice->invoice_date?->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <span class="customer-avatar">{{ strtoupper(substr((string) $invoice->buyer_name, 0, 1)) ?: 'C' }}</span>
                                {{ $invoice->buyer_name ?: 'Walk-in Buyer' }}
                            </td>
                            <td><span class="status-pill status-{{ $invoice->status->value }}">{{ ucfirst($invoice->status->value) }}</span></td>
                            <td class="text-end fw-bold">PKR {{ number_format($invoice->grand_total, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">No invoices yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <aside class="dashboard-side">
        @if(auth()->user()?->canEditInvoices())
            <div class="panel dashboard-actions-panel">
                <div class="panel-header"><h2>Quick Actions</h2></div>
                <div class="dashboard-action-grid">
                    <a href="{{ route('invoices.create') }}"><i class="bi bi-plus-circle-fill text-primary"></i><span>Invoice</span></a>
                    <a href="{{ route('customers.create') }}"><i class="bi bi-person-plus-fill text-success"></i><span>Customer</span></a>
                    <a href="{{ route('imports.index') }}"><i class="bi bi-file-earmark-excel-fill text-info"></i><span>Import</span></a>
                    <a href="{{ route('dashboard') }}"><i class="bi bi-arrow-clockwise text-warning"></i><span>Refresh</span></a>
                </div>
            </div>
        @endif

        <div class="dashboard-ring-card sync-card">
            <h2>Submission Success Rate</h2>
            <div class="dashboard-ring" style="--value: {{ $syncRate }}%">
                <strong>{{ number_format($submissionSuccessRate, 0) }}%</strong>
            </div>
            <p>{{ number_format($submissionSuccess) }} of {{ number_format($submissionAttempts) }} synced with FBR</p>
        </div>

        <div class="dashboard-ring-card quota-card">
            <h2>Monthly Invoice Usage</h2>
            <div class="dashboard-ring quota-ring" style="--value: {{ $quotaRate }}%">
                <strong>{{ number_format($quotaUsed) }}/{{ number_format($quotaLimit) }}</strong>
            </div>
            <p>{{ number_format($quotaRemaining) }} invoices remaining this month</p>
        </div>

        <div class="panel dashboard-customers-panel">
            <div class="panel-header"><h2>Top Customers</h2></div>
            <div class="dashboard-customer-list">
                @forelse($topCustomers as $customer)
                    <div class="dashboard-customer-row">
                        <span>{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                        <div><strong>{{ $customer->name }}</strong><small>{{ number_format($customer->invoice_count) }} invoices</small></div>
                        <b>PKR {{ number_format($customer->total_revenue, 0) }}</b>
                    </div>
                @empty
                    <div class="text-secondary">No customer activity yet.</div>
                @endforelse
            </div>
            <a class="btn btn-outline-light w-100 mt-3" href="{{ route('customers.index') }}">Manage Customers</a>
        </div>
    </aside>
</div>

@push('scripts')
<script>
const dashboardInvoiceCounts = @json($monthlyInvoiceCount);
new Chart(document.getElementById('dashboardChart'), {
    type: 'bar',
    data: {
        labels: @json($monthlyLabels),
        datasets: [
            {
                type: 'bar',
                label: 'Revenue',
                data: @json($monthlyRevenue),
                backgroundColor: 'rgba(13, 110, 253, .82)',
                borderRadius: 8,
                yAxisID: 'y'
            },
            {
                type: 'line',
                label: 'Invoices',
                data: dashboardInvoiceCounts,
                borderColor: '#16a34a',
                backgroundColor: '#16a34a',
                tension: .35,
                pointRadius: 3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', align: 'end' } },
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { position: 'left', grid: { color: 'rgba(141, 138, 163, .18)' }, ticks: { callback: value => 'PKR ' + Number(value).toLocaleString() } },
            y1: {
                position: 'right',
                beginAtZero: true,
                suggestedMax: Math.max(...dashboardInvoiceCounts, 5),
                grid: { drawOnChartArea: false },
                ticks: { precision: 0 }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
@endsection
