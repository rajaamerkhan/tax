@csrf
@if($client->exists)
    @method('PUT')
@endif
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Client Name</label>
        <input class="form-control" name="name" value="{{ old('name', $client->name) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="active" @selected(old('status', $client->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="{{ old('phone', $client->phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Client Email</label>
        <input class="form-control" type="email" name="email" value="{{ old('email', $client->email) }}" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" data-no-autofill data-lpignore="true" data-1p-ignore>
    </div>
    <div class="col-md-3">
        <label class="form-label">Monthly Invoice Limit</label>
        <input class="form-control" type="number" name="max_invoices_per_month" min="0" value="{{ old('max_invoices_per_month', $client->max_invoices_per_month ?? 30) }}" required>
    </div>
</div>

<div class="mt-4">
    <h2 class="h5 mb-3">Client Login</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Login Email</label>
            <input class="form-control" type="email" name="admin_email" value="{{ old('admin_email', $admin->email) }}" required autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" data-no-autofill data-lpignore="true" data-1p-ignore>
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ $client->exists ? 'New Password' : 'Password' }}</label>
            <input class="form-control" type="password" name="admin_password" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore @required(! $client->exists)>
        </div>
        <div class="col-md-3">
            <label class="form-label">Confirm Password</label>
            <input class="form-control" type="password" name="admin_password_confirmation" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore @required(! $client->exists)>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">{{ $client->exists ? 'Save Client' : 'Create Client' }}</button>
    <a class="btn btn-outline-light" href="{{ route('owner.clients.index') }}">Cancel</a>
</div>
