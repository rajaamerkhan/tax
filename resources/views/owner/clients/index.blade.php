@extends('layouts.app')
@section('title', 'Clients')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h2>Clients</h2>
        <a href="{{ route('owner.clients.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Client</a>
    </div>
    <form class="row g-3 mb-3">
        <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search clients"></div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Search</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Client</th><th>Contact</th><th>Email</th><th>Status</th><th>Users</th><th></th></tr></thead>
            <tbody>
            @forelse($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->contact_name }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ ucfirst($client->status) }}</td>
                    <td>{{ $client->users_count }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <form method="POST" action="{{ route('owner.clients.manage', $client) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary" @disabled($client->status !== 'active')>Manage</button>
                            </form>
                            <a class="btn btn-sm btn-outline-light" href="{{ route('owner.clients.edit', $client) }}">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">No clients found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $clients->links() }}
</div>
@endsection
