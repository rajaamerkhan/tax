@extends('layouts.app')
@section('title', 'Customer Details')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h2>{{ $customer->name }}</h2>
        <div class="d-flex gap-2">
            @if(auth()->user()?->canEditInvoices())
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-light">Edit</a>
            @endif
            @if(auth()->user()?->isManagingClient())
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer? The record will be hidden but kept in the database.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-4"><div class="metric-label">CNIC / Registration No.</div><div>{{ $customer->ntn_cnic ?: 'N/A' }}</div></div>
        <div class="col-md-4"><div class="metric-label">Tax Number (NTN)</div><div>{{ $customer->strn ?: 'N/A' }}</div></div>
        <div class="col-md-4"><div class="metric-label">Buyer Type</div><div>{{ ucfirst(optional($customer->buyer_type)->value ?? $customer->buyer_type) }}</div></div>
        <div class="col-md-4"><div class="metric-label">Province</div><div>{{ $customer->province?->name ?: 'N/A' }}</div></div>
        <div class="col-md-4"><div class="metric-label">Status</div><div>{{ ucfirst(optional($customer->status)->value ?? $customer->status) }}</div></div>
        <div class="col-12"><div class="metric-label">Address</div><div>{{ $customer->address ?: 'N/A' }}</div></div>
    </div>
</div>
@endsection
