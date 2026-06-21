@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h2>Edit User</h2>
        @if(! $managedUser->is(auth()->user()))
            <form method="POST" action="{{ route('users.destroy', $managedUser) }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger">Delete User</button>
            </form>
        @endif
    </div>
    <form method="POST" action="{{ route('users.update', $managedUser) }}">
        @include('users._form')
    </form>
</div>
@endsection
