@extends('layouts.app')
@section('title', 'Invoice Details')
@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="mb-1">{{ $invoice->invoice_number }}</h2>
        <div class="text-secondary">{{ $invoice->buyer_name }} | {{ $invoice->invoice_date?->format('d M Y') }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="status-pill status-{{ $invoice->status->value }}">{{ ucfirst($invoice->status->value) }}</span>
        @if(! $invoice->isLocked())<a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline-light">Edit</a>@endif
        <form method="POST" action="{{ route('invoices.validate-fbr', $invoice) }}">@csrf <button class="btn btn-outline-light">Validate with FBR</button></form>
        <form method="POST" action="{{ route('invoices.submit-fbr', $invoice) }}">@csrf <button class="btn btn-primary">Submit to FBR</button></form>
        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-light">Print</a>
        <a href="{{ route('invoices.download-pdf', $invoice) }}" class="btn btn-outline-light">Download PDF</a>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="panel"><div class="panel-header"><h2>Invoice Items</h2></div><div class="table-responsive"><table class="table"><thead><tr><th>Description</th><th>HS</th><th>Qty</th><th>Rate %</th><th>Value Excl. ST</th><th>Sales Tax</th><th>Total</th></tr></thead><tbody>@foreach($invoice->items as $item)<tr><td>{{ $item->description }}</td><td>{{ $item->hs_code }}</td><td>{{ $item->quantity }}</td><td>{{ $item->rate_percent }}</td><td>{{ number_format($item->value_excluding_sales_tax, 2) }}</td><td>{{ number_format($item->sales_tax, 2) }}</td><td>{{ number_format($item->total_value, 2) }}</td></tr>@endforeach</tbody></table></div></div></div>
    <div class="col-lg-4"><div class="panel"><div class="panel-header"><h2>Summary</h2></div><div class="summary-grid vertical"><div><span>Value Excl. ST</span><strong>PKR {{ number_format($invoice->value_excluding_sales_tax, 2) }}</strong></div><div><span>Sales Tax</span><strong>PKR {{ number_format($invoice->sales_tax_amount, 2) }}</strong></div><div><span>Extra Tax</span><strong>PKR {{ number_format($invoice->extra_tax_amount, 2) }}</strong></div><div><span>Further Tax</span><strong>PKR {{ number_format($invoice->further_tax_amount, 2) }}</strong></div><div><span>FED</span><strong>PKR {{ number_format($invoice->fed_amount, 2) }}</strong></div><div><span>Discount</span><strong>PKR {{ number_format($invoice->discount_amount, 2) }}</strong></div><div><span>Grand Total</span><strong>PKR {{ number_format($invoice->grand_total, 2) }}</strong></div>@if($invoice->editable_until)<div><span>Editable Until</span><strong class="countdown" data-until="{{ $invoice->editable_until->toIso8601String() }}">{{ $invoice->editable_until->format('d M Y H:i') }}</strong></div>@endif<div><span>FBR Invoice ID</span><strong>{{ $invoice->fbr_invoice_id ?: 'Pending' }}</strong></div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h2>Buyer</h2></div><div class="metric-label">Name</div><div>{{ $invoice->buyer_name }}</div><div class="metric-label mt-3">CNIC / NTN</div><div>{{ $invoice->buyer_ntn_cnic ?: 'N/A' }}</div><div class="metric-label mt-3">STRN</div><div>{{ $invoice->buyer_strn ?: 'N/A' }}</div><div class="metric-label mt-3">Address</div><div>{{ $invoice->buyer_address ?: 'N/A' }}</div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h2>FBR Response</h2></div><pre class="json-box">{{ json_encode($invoice->fbr_response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div></div>
</div>
@endsection
