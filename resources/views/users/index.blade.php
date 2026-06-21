@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h2>Users</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New User</a>
    </div>
    <form class="row g-3 mb-3">
        <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search by name, email, phone"></div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Search</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Last Login</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $managedUser)
                <tr>
                    <td>{{ $managedUser->name }}</td>
                    <td>{{ $managedUser->email }}</td>
                    <td>{{ $managedUser->phone }}</td>
                    <td>{{ $managedUser->role->label() }}</td>
                    <td>{{ $managedUser->last_login_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-light" href="{{ route('users.edit', $managedUser) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
