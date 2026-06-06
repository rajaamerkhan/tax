@extends('layouts.app')
@section('title', 'Mock FBR Console')
@section('content')
<div class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center">
        <h2>Mock FBR Request / Response Console</h2>
        <form method="POST" action="{{ route('admin.mock-fbr-console.demo-invoice') }}">
            @csrf
            <button class="btn btn-primary">Submit Demo Invoice</button>
        </form>
    </div>
    <form class="row g-3 mb-3">
        <div class="col-md-4"><input class="form-control" name="endpoint" value="{{ request('endpoint') }}" placeholder="Filter by endpoint"></div>
        <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><option value="Valid" @selected(request('status')==='Valid')>Valid</option><option value="Invalid" @selected(request('status')==='Invalid')>Invalid</option><option value="failed" @selected(request('status')==='failed')>failed</option></select></div>
        <div class="col-md-2"><button class="btn btn-outline-light w-100">Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Time</th><th>Invoice</th><th>Endpoint</th><th>Status</th><th>HTTP</th><th>Payloads</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                    <td>@if($log->invoice)<a href="{{ route('invoices.show', $log->invoice) }}">{{ $log->invoice->invoice_number }}</a>@else N/A @endif</td>
                    <td><code>{{ $log->endpoint }}</code></td>
                    <td>{{ $log->status }}</td>
                    <td>{{ $log->http_status ?: 'N/A' }}</td>
                    <td>
                        <details>
                            <summary>Request</summary>
                            <pre class="json-box mt-2">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                        <details class="mt-2">
                            <summary>Response</summary>
                            <pre class="json-box mt-2">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                        @if($log->error_message)
                            <div class="text-danger small mt-2">{{ $log->error_message }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">No FBR logs captured yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
