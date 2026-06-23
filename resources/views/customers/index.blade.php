@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h2>Customers</h2>
        @if(auth()->user()?->canEditInvoices())
            <a href="{{ route('customers.create') }}" class="btn btn-primary">New Customer</a>
        @endif
    </div>
    <form class="row g-3 mb-3"><div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search by name, CNIC/NTN, STRN"></div><div class="col-md-2"><button class="btn btn-outline-light w-100">Search</button></div></form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>CNIC / NTN</th><th>STRN</th><th>Buyer Type</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td><td>{{ $customer->ntn_cnic }}</td><td>{{ $customer->strn }}</td><td>{{ ucfirst(optional($customer->buyer_type)->value ?? $customer->buyer_type) }}</td><td>{{ ucfirst(optional($customer->status)->value ?? $customer->status) }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a class="btn btn-sm btn-outline-light" href="{{ route('customers.show', $customer) }}">View</a>
                            @if(auth()->user()?->isManagingClient())
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer? The record will be hidden but kept in the database.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">No customers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</div>
@endsection
