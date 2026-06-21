@extends('layouts.app')
@section('title', 'Company Profile')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center"><h2>Company Profile</h2><form method="POST" action="{{ route('company.sync-references') }}">@csrf <button class="btn btn-outline-light">Sync FBR Reference Data</button></form></div>
    <form method="POST" action="{{ route('company.update') }}" class="row g-3">
        @csrf @method('PUT')
        <div class="col-md-6"><label class="form-label">Company Name</label><input class="form-control" name="name" value="{{ old('name', $company->name) }}"></div>
        <div class="col-md-3"><label class="form-label">NTN / CNIC</label><input class="form-control" name="ntn_cnic" value="{{ old('ntn_cnic', $company->ntn_cnic) }}"></div>
        <div class="col-md-3"><label class="form-label">STRN</label><input class="form-control" name="strn" value="{{ old('strn', $company->strn) }}"></div>
        <div class="col-md-4"><label class="form-label">Province</label><select class="form-select" name="province_id"><option value="">Select</option>@foreach($provinces as $province)<option value="{{ $province->id }}" @selected(old('province_id', $company->province_id) == $province->id)>{{ $province->display_name ?? $province->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $company->phone) }}"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $company->email) }}" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" data-no-autofill data-lpignore="true" data-1p-ignore></div>
        <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">{{ old('address', $company->address) }}</textarea></div>
        <div class="col-md-4"><label class="form-label">FBR Environment</label><select class="form-select" name="fbr_environment"><option value="sandbox" @selected(old('fbr_environment', optional($company->fbr_environment)->value) === 'sandbox')>Sandbox</option><option value="production" @selected(old('fbr_environment', optional($company->fbr_environment)->value) === 'production')>Production</option></select></div>
        <div class="col-md-4"><label class="form-label">Sandbox Token</label><input class="form-control" name="fbr_sandbox_token" value="{{ old('fbr_sandbox_token') }}" placeholder="Leave blank to keep current token" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
        <div class="col-md-4"><label class="form-label">Production Token</label><input class="form-control" name="fbr_production_token" value="{{ old('fbr_production_token') }}" placeholder="Leave blank to keep current token" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
        <div class="col-md-4"><label class="form-label">Business Nature</label><select class="form-select" name="fbr_business_nature"><option value="">Select</option>@foreach($businessNatures as $value => $label)<option value="{{ $value }}" @selected(old('fbr_business_nature', $company->fbr_business_nature) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12"><button class="btn btn-primary">Save Company Profile</button></div>
    </form>
</div>
@endsection
