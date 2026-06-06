@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center"><h2>Invoices</h2><a href="{{ route('invoices.create') }}" class="btn btn-primary">Create Invoice</a></div>
    <form class="row g-3 mb-3">
        <div class="col-md-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Invoice / buyer / NTN"></div>
        <div class="col-md-2"><select class="form-select" name="status"><option value="">All Statuses</option>@foreach(['draft','validated','submitted','failed','editable','locked','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select class="form-select" name="lock_state"><option value="">All Lock States</option><option value="editable" @selected(request('lock_state')==='editable')>Editable</option><option value="locked" @selected(request('lock_state')==='locked')>Locked</option></select></div>
        <div class="col-md-2"><input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-2"><input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-md-1"><button class="btn btn-outline-light w-100">Go</button></div>
    </form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Invoice</th><th>Buyer</th><th>Status</th><th>Editable Until</th><th>Total</th><th></th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td>{{ $invoice->invoice_number }}</td><td>{{ $invoice->buyer_name }}</td><td><span class="status-pill status-{{ $invoice->status->value }}">{{ ucfirst($invoice->status->value) }}</span></td><td>{{ $invoice->editable_until?->format('d M Y H:i') ?: 'N/A' }}</td><td>PKR {{ number_format($invoice->grand_total, 2) }}</td><td class="text-end"><a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-light">View</a></td></tr>@empty<tr><td colspan="6" class="text-secondary">No invoices found.</td></tr>@endforelse</tbody></table></div>
    {{ $invoices->links() }}
</div>
@endsection
