@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>User Profile</h2></div>
            @if($isManagedClientProfile)
                <div class="alert alert-info border-0 shadow-sm">Editing the selected client account profile.</div>
            @endif
            <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name', $profileUser->name) }}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $profileUser->email) }}" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $profileUser->phone) }}"></div>
                <div class="col-12"><button class="btn btn-primary">Update Profile</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Change Password</h2></div>
            <form method="POST" action="{{ route('profile.password') }}" class="row g-3">
                @csrf @method('PUT')
                @unless($isManagedClientProfile)
                    <div class="col-12"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                @endunless
                <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="password" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="password_confirmation" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore></div>
                <div class="col-12"><button class="btn btn-primary">Change Password</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
