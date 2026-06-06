@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>User Profile</h2></div>
            <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name', auth()->user()->name) }}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email" value="{{ old('email', auth()->user()->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div>
                <div class="col-12"><button class="btn btn-primary">Update Profile</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Change Password</h2></div>
            <form method="POST" action="{{ route('profile.password') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-12"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password"></div>
                <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="password"></div>
                <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="password_confirmation"></div>
                <div class="col-12"><button class="btn btn-primary">Change Password</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
