<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        .heading { margin-bottom: 18px; }
        .heading h1 { margin: 0 0 8px; font-size: 24px; }
        .grid { display: table; width: 100%; margin-bottom: 18px; }
        .col { display: table-cell; width: 50%; vertical-align: top; }
        .summary td { border: none; padding: 4px 0; }
    </style>
</head>
<body>
<div class="heading">
    <h1>Sales Tax Invoice</h1>
    <div>Invoice No: {{ $invoice->invoice_number }}</div>
    <div>Invoice Date: {{ $invoice->invoice_date?->format('d M Y') }}</div>
    <div>FBR Invoice ID: {{ $invoice->fbr_invoice_id ?: 'Pending' }}</div>
</div>
<div class="grid">
    <div class="col">
        <strong>Seller</strong><br>
        {{ optional(\App\Models\CompanyProfile::first())->name }}<br>
        {{ optional(\App\Models\CompanyProfile::first())->ntn_cnic }}<br>
        {{ optional(\App\Models\CompanyProfile::first())->address }}
    </div>
    <div class="col">
        <strong>Buyer</strong><br>
        {{ $invoice->buyer_name }}<br>
        {{ $invoice->buyer_ntn_cnic }}<br>
        {{ $invoice->buyer_address }}
    </div>
</div>
<table>
    <thead>
    <tr><th>#</th><th>Description</th><th>HS Code</th><th>Qty</th><th>Rate %</th><th>Value Excl. ST</th><th>Sales Tax</th><th>Total</th></tr>
    </thead>
    <tbody>
    @foreach($invoice->items as $item)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $item->description }}</td><td>{{ $item->hs_code }}</td><td>{{ $item->quantity }}</td><td>{{ $item->rate_percent }}</td><td>{{ number_format($item->value_excluding_sales_tax, 2) }}</td><td>{{ number_format($item->sales_tax, 2) }}</td><td>{{ number_format($item->total_value, 2) }}</td></tr>
    @endforeach
    </tbody>
</table>
<table class="summary" style="margin-top: 18px;">
    <tr><td><strong>Value Excl. ST</strong></td><td>PKR {{ number_format($invoice->value_excluding_sales_tax, 2) }}</td></tr>
    <tr><td><strong>Sales Tax</strong></td><td>PKR {{ number_format($invoice->sales_tax_amount, 2) }}</td></tr>
    <tr><td><strong>Extra Tax</strong></td><td>PKR {{ number_format($invoice->extra_tax_amount, 2) }}</td></tr>
    <tr><td><strong>Further Tax</strong></td><td>PKR {{ number_format($invoice->further_tax_amount, 2) }}</td></tr>
    <tr><td><strong>FED</strong></td><td>PKR {{ number_format($invoice->fed_amount, 2) }}</td></tr>
    <tr><td><strong>Discount</strong></td><td>PKR {{ number_format($invoice->discount_amount, 2) }}</td></tr>
    <tr><td><strong>Grand Total</strong></td><td>PKR {{ number_format($invoice->grand_total, 2) }}</td></tr>
</table>
@if($invoice->qr_code_path)
    <div style="margin-top: 24px;">
        <strong>Verification QR</strong><br>
        <img src="{{ storage_path('app/public/'.$invoice->qr_code_path) }}" width="140" alt="QR Code"><br>
        <span>Click to verify: {{ rtrim((string) config('fbr.verify_url'), '/') }}/{{ $invoice->fbr_invoice_id }}</span>
    </div>
@endif
</body>
</html>
