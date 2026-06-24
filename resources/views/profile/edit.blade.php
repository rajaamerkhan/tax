@extends('layouts.app')
@section('title', 'My Profile')
@section('content')
<div class="row g-4">
    <div class="col-xl-6">
        <div class="panel profile-panel profile-panel-primary">
            <div class="panel-header"><h2><i class="bi bi-person-fill"></i> User Information</h2></div>
            <div class="table-responsive">
                <table class="table profile-info-table mb-0">
                    <tbody>
                    <tr><th>Username</th><td>{{ $profileUser->name }}</td></tr>
                    <tr><th>Email</th><td>{{ $profileUser->email }}</td></tr>
                    <tr><th>Phone</th><td>{{ $profileUser->phone ?: 'Not set' }}</td></tr>
                    <tr><th>Role</th><td>{{ $profileUser->role->label() }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel mt-4">
            <div class="panel-header"><h2><i class="bi bi-key-fill"></i> Change Password</h2></div>
            <form method="POST" action="{{ route('profile.password') }}" class="row g-3">
                @csrf @method('PUT')
                @unless($isManagedClientProfile)
                    <div class="col-12"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                @endunless
                <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="password" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="password_confirmation" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                <div class="col-12"><button class="btn btn-primary"><i class="bi bi-key"></i> Change Password</button></div>
            </form>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="panel profile-panel profile-panel-success">
            <div class="panel-header"><h2><i class="bi bi-building-fill"></i> Company Information</h2></div>
            <div class="table-responsive">
                <table class="table profile-info-table mb-0">
                    <tbody>
                    <tr><th>Company Name</th><td>{{ $company?->name ?: $client?->name ?: 'Not set' }}</td></tr>
                    <tr><th>CNIC / Registration No.</th><td>{{ $company?->fbr_registration_number ?: 'Not set' }}</td></tr>
                    <tr><th>Tax Number (NTN)</th><td>{{ $company?->ntn_cnic ?: 'Not set' }}</td></tr>
                    <tr><th>Email</th><td>{{ $company?->email ?: $client?->email ?: 'Not set' }}</td></tr>
                    <tr><th>Phone</th><td>{{ $company?->phone ?: $client?->phone ?: 'Not set' }}</td></tr>
                    <tr><th>Address</th><td>{{ $company?->address ?: 'Not set' }}</td></tr>
                    <tr>
                        <th>Billing Plan</th>
                        <td>
                            <strong>Basic</strong>
                            <div class="text-secondary">Range: 0 - {{ number_format($invoiceLimit) }} invoices/month</div>
                            <div class="text-secondary">Used this month: {{ number_format($usedInvoices) }} / {{ number_format($invoiceLimit) }}</div>
                            <div class="text-secondary">Remaining: {{ number_format($remainingInvoices) }}</div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
