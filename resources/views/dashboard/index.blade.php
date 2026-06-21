@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="label">Total Invoices</div><div class="value">{{ number_format($totalInvoices) }}</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="label">Total Tax Amount</div><div class="value">PKR {{ number_format($totalTaxAmount, 2) }}</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="label">Pending FBR</div><div class="value">{{ number_format($pendingFbrSubmissions) }}</div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="label">Success Rate</div><div class="value">{{ number_format($submissionSuccessRate, 2) }}%</div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><h2>Monthly Revenue & Invoice Count</h2></div>
            <canvas id="dashboardChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2>Top Customers</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Customer</th><th>Invoices</th><th>Revenue</th></tr></thead>
                    <tbody>
                    @forelse($topCustomers as $customer)
                        <tr><td>{{ $customer->name }}</td><td>{{ $customer->invoice_count }}</td><td>PKR {{ number_format($customer->total_revenue, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">No customer activity yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><h2>Recent Invoices</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Invoice</th><th>Buyer</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($recentInvoices as $invoice)
                        <tr>
                            <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ $invoice->buyer_name }}</td>
                            <td><span class="status-pill status-{{ $invoice->status->value }}">{{ ucfirst($invoice->status->value) }}</span></td>
                            <td>PKR {{ number_format($invoice->grand_total, 2) }}</td>
                            <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">No invoices yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @if(auth()->user()?->canEditInvoices())
            <div class="panel quick-actions">
                <div class="panel-header"><h2>Quick Actions</h2></div>
                <a href="{{ route('invoices.create') }}" class="btn btn-primary w-100">Create Invoice</a>
                <a href="{{ route('imports.index') }}" class="btn btn-outline-light w-100">Import Invoices</a>
                <a href="{{ route('customers.create') }}" class="btn btn-outline-light w-100">Add Customer</a>
            </div>
        @endif
    </div>
</div>
@push('scripts')
<script>
new Chart(document.getElementById('dashboardChart'), {
    type: 'line',
    data: {
        labels: @json($monthlyLabels),
        datasets: [
            {label: 'Revenue', data: @json($monthlyRevenue), borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,.15)', yAxisID: 'y'},
            {label: 'Invoices', data: @json($monthlyInvoiceCount), borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.15)', yAxisID: 'y1'}
        ]
    },
    options: {responsive: true, interaction: {mode: 'index'}, scales: {y: {position: 'left'}, y1: {position: 'right', grid: {drawOnChartArea: false}}}}
});
</script>
@endpush
@endsection
