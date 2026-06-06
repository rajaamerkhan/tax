@extends('layouts.app')
@section('title', 'Import Invoices')
@section('content')
<div class="row g-4">
    <div class="col-lg-5"><div class="panel"><div class="panel-header"><h2>Upload Excel / CSV</h2></div><form method="POST" action="{{ route('imports.preview') }}" enctype="multipart/form-data" class="row g-3">@csrf<div class="col-12"><label class="form-label">Invoice File</label><input type="file" class="form-control" name="file"></div><div class="col-12 d-flex gap-2"><button class="btn btn-primary">Preview Import</button><a href="{{ route('imports.template') }}" class="btn btn-outline-light">Download Sample Template</a></div></form></div></div>
    <div class="col-lg-7"><div class="panel"><div class="panel-header"><h2>Recent Import Batches</h2></div><div class="table-responsive"><table class="table"><thead><tr><th>File</th><th>Status</th><th>Imported</th><th></th></tr></thead><tbody>@forelse($batches as $batch)<tr><td>{{ $batch->filename }}</td><td>{{ ucfirst($batch->status) }}</td><td>{{ $batch->imported_count }}</td><td class="text-end"><a href="{{ route('imports.show', $batch) }}" class="btn btn-sm btn-outline-light">View</a></td></tr>@empty<tr><td colspan="4" class="text-secondary">No imports yet.</td></tr>@endforelse</tbody></table></div></div></div>
</div>
@endsection
