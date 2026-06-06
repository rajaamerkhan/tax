@extends('layouts.app')
@section('title', 'Reference Data')
@section('content')
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2>Import HS / Custom Duty Data</h2></div>
            <form method="POST" action="{{ route('reference-data.hs-codes.import') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-12"><label class="form-label">CSV / Excel File</label><input type="file" class="form-control" name="file"></div>
                <div class="col-12 small text-secondary">Columns supported: <code>hs_code</code>, <code>description</code>, <code>custom_duty_code</code>.</div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Import HS Codes</button><a href="{{ route('reference-data.hs-codes.template') }}" class="btn btn-outline-light">Download Template</a></div>
            </form>
        </div>
        <div class="panel mt-4">
            <div class="panel-header"><h2>Recent Imports</h2></div>
            <div class="table-responsive">
                <table class="table"><thead><tr><th>File</th><th>Status</th><th>Imported</th><th>Errors</th></tr></thead><tbody>@forelse($imports as $import)<tr><td>{{ $import->filename }}</td><td>{{ $import->status }}</td><td>{{ $import->imported_count }}</td><td>@if(!empty($import->errors))<details><summary>{{ count($import->errors) }} row(s)</summary><pre class="json-box mt-2">{{ json_encode($import->errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>@else<span class="text-secondary">None</span>@endif</td></tr>@empty<tr><td colspan="4" class="text-secondary">No imports yet.</td></tr>@endforelse</tbody></table>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><h2>HS Codes</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>HS Code</th><th>Description</th><th>Custom Duty</th></tr></thead>
                    <tbody>
                    @forelse($hsCodes as $hsCode)
                        <tr>
                            <td>{{ $hsCode->code }}</td>
                            <td>{{ $hsCode->description }}</td>
                            <td>{{ $hsCode->custom_duty_code ?? '0' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">No HS codes available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $hsCodes->links() }}
        </div>
    </div>
</div>
@endsection
