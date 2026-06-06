@extends('layouts.app')
@section('title', 'Import Preview')
@section('content')
<div class="panel mb-4"><div class="panel-header d-flex justify-content-between align-items-center"><h2>{{ $batch->filename }}</h2>@if(empty($batch->errors) && $batch->status !== 'imported')<form method="POST" action="{{ route('imports.store', $batch) }}">@csrf<button class="btn btn-primary">Import as Draft Invoices</button></form>@endif</div><div class="row g-3"><div class="col-md-3"><div class="metric-label">Status</div><div>{{ ucfirst($batch->status) }}</div></div><div class="col-md-3"><div class="metric-label">Imported Count</div><div>{{ $batch->imported_count }}</div></div></div></div>
@if(! empty($batch->errors))
<div class="panel mb-4"><div class="panel-header"><h2>Row Errors</h2></div><div class="json-box">{{ json_encode($batch->errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</div></div>
@endif
<div class="panel"><div class="panel-header"><h2>Preview Rows</h2></div><div class="table-responsive"><table class="table"><thead><tr><th>Invoice</th><th>Date</th><th>Buyer</th><th>Item</th><th>Qty</th><th>Price</th></tr></thead><tbody>@foreach(($batch->preview_rows ?? []) as $row)<tr><td>{{ $row['invoice_number'] }}</td><td>{{ $row['invoice_date'] }}</td><td>{{ $row['buyer_name'] }}</td><td>{{ $row['item_description'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ $row['unit_price'] }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
