<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #202534;
            font-size: 11px;
            margin: 22px 28px;
        }
        * {
            box-sizing: border-box;
        }
        .invoice-shell {
            width: 100%;
        }
        .top-verify-bar {
            width: 100%;
            border-top: 1px solid #d7dce8;
            border-bottom: 1px solid #d7dce8;
            padding: 14px 0 18px;
            margin-bottom: 22px;
        }
        .top-verify-table,
        .header-grid,
        .meta-grid,
        .summary-grid,
        .footer-verify-table {
            width: 100%;
            border-collapse: collapse;
        }
        .top-verify-left {
            font-size: 11px;
            color: #5b6478;
            font-weight: 600;
            vertical-align: middle;
        }
        .top-verify-left strong {
            color: #2a3347;
        }
        .top-verify-right {
            width: 170px;
            text-align: right;
            vertical-align: middle;
        }
        .verify-qr-box {
            display: inline-block;
            text-align: center;
        }
        .verify-caption {
            margin-top: 4px;
            font-size: 10px;
            color: #5b6478;
        }
        .seller-header {
            text-align: center;
            margin-bottom: 18px;
        }
        .seller-header h1 {
            margin: 0 0 8px;
            font-size: 22px;
            letter-spacing: .02em;
            font-weight: 800;
            color: #101729;
        }
        .seller-header h2 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 800;
            color: #111827;
        }
        .seller-line {
            color: #5a6578;
            line-height: 1.5;
            font-size: 11px;
        }
        .section-rule {
            height: 3px;
            background: #1677ff;
            margin: 16px 0 14px;
        }
        .meta-cell {
            width: 50%;
            vertical-align: top;
            border: 1px solid #e5e9f2;
            padding: 12px 14px;
        }
        .meta-title {
            font-size: 12px;
            font-weight: 800;
            color: #1677ff;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .fact-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fact-table td {
            padding: 1px 0;
            vertical-align: top;
            border: none;
        }
        .fact-label {
            width: 102px;
            font-weight: 800;
            color: #1f2937;
            white-space: nowrap;
        }
        .fact-value {
            color: #172033;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .item-table thead th {
            border: 1px solid #dfe5ef;
            background: #f8fbff;
            color: #2c3b52;
            text-align: center;
            padding: 7px 5px;
            font-size: 10px;
            font-weight: 800;
        }
        .item-table tbody td {
            border: 1px solid #e3e8f1;
            padding: 6px 5px;
            vertical-align: top;
            font-size: 10px;
        }
        .item-center {
            text-align: center;
        }
        .item-right {
            text-align: right;
            white-space: nowrap;
        }
        .item-desc {
            font-weight: 700;
            color: #1d2638;
            margin-bottom: 3px;
        }
        .item-subline {
            color: #556176;
            line-height: 1.35;
        }
        .totals-wrap {
            width: 100%;
            margin-top: 12px;
        }
        .summary-box {
            width: 44%;
            margin-left: auto;
            border-top: 3px solid #1677ff;
            padding-top: 8px;
        }
        .summary-grid td {
            padding: 2px 0;
            border: none;
            font-size: 11px;
        }
        .summary-label {
            color: #1f2937;
        }
        .summary-value {
            text-align: right;
            font-weight: 800;
            white-space: nowrap;
        }
        .summary-discount .summary-label,
        .summary-discount .summary-value {
            color: #ea5455;
        }
        .summary-total {
            border-top: 1px solid #d9e1ef;
        }
        .summary-total td {
            padding-top: 6px;
        }
        .summary-total .summary-label,
        .summary-total .summary-value {
            color: #28a463;
            font-size: 13px;
            font-weight: 900;
        }
        .notes-box {
            margin-top: 18px;
            border: 1px solid #e4e8f1;
            padding: 10px 12px;
            min-height: 48px;
            color: #445066;
        }
        .notes-title {
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .footer-verify {
            margin-top: 26px;
            border-top: 1px solid #d7dce8;
            padding-top: 16px;
        }
        .footer-verify-left {
            vertical-align: middle;
            color: #59647a;
            font-size: 11px;
            font-weight: 600;
        }
        .footer-verify-left strong {
            color: #253046;
        }
        .footer-verify-right {
            width: 250px;
            text-align: right;
            vertical-align: middle;
        }
        .footer-verify-pack {
            display: inline-table;
            border-collapse: collapse;
        }
        .footer-verify-pack td {
            border: none;
            vertical-align: top;
            padding: 0;
        }
        .footer-verify-pack .qr-cell {
            padding-right: 10px;
        }
        .fbr-logo-img {
            width: 82px;
            height: auto;
            display: block;
        }
        .verify-url {
            display: block;
            margin-top: 5px;
            font-size: 9px;
            color: #6a7284;
            word-break: break-all;
        }
    </style>
</head>
<body>
@php
    $company = \App\Models\CompanyProfile::with('province')->first();
    $verificationDisplay = $verificationUrl ?? ($invoice->fbr_invoice_id ?: null);
    $fbrLogoPath = public_path('assets/img/fbr-digital-logo.png');
    $fbrLogoDataUri = is_file($fbrLogoPath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($fbrLogoPath))
        : null;
@endphp
<div class="invoice-shell">
    <div class="top-verify-bar">
        <table class="top-verify-table">
            <tr>
                <td class="top-verify-left">
                    <strong>FBR Invoice ID:</strong> {{ $invoice->fbr_invoice_id ?: 'Pending submission' }}
                </td>
                <td class="top-verify-right">
                    &nbsp;
                </td>
            </tr>
        </table>
    </div>

    <div class="seller-header">
        <h1>SALES TAX INVOICE</h1>
        <h2>{{ $company?->name ?: 'Company Name' }}</h2>
        <div class="seller-line">
            {{ $company?->address ?: 'Company address not configured' }}
        </div>
        <div class="seller-line">
            NTN/CNIC: {{ $company?->ntn_cnic ?: 'N/A' }}
            @if($company?->phone)
                | Phone: {{ $company->phone }}
            @endif
            @if($company?->email)
                | Email: {{ $company->email }}
            @endif
        </div>
    </div>

    <div class="section-rule"></div>

    <table class="meta-grid">
        <tr>
            <td class="meta-cell">
                <div class="meta-title">Buyer Information</div>
                <table class="fact-table">
                    <tr>
                        <td class="fact-label">Name:</td>
                        <td class="fact-value">{{ $invoice->buyer_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">NTN/CNIC:</td>
                        <td class="fact-value">{{ $invoice->buyer_ntn_cnic ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">Address:</td>
                        <td class="fact-value">{{ $invoice->buyer_address ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">STRN:</td>
                        <td class="fact-value">{{ $invoice->buyer_strn ?: 'N/A' }}</td>
                    </tr>
                </table>
            </td>
            <td class="meta-cell">
                <div class="meta-title">Invoice Details</div>
                <table class="fact-table">
                    <tr>
                        <td class="fact-label">Invoice #:</td>
                        <td class="fact-value">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">Date:</td>
                        <td class="fact-value">{{ $invoice->invoice_date?->format('d-M-Y') ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">Scenario:</td>
                        <td class="fact-value">{{ $invoice->scenario?->code ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">Type:</td>
                        <td class="fact-value">{{ $invoice->invoice_type ?: 'Sale Invoice' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">Origin:</td>
                        <td class="fact-value">{{ $invoice->saleOriginProvince?->name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fact-label">Destination:</td>
                        <td class="fact-value">{{ $invoice->destinationProvince?->name ?: 'N/A' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 10%;">HS Code</th>
            <th style="width: 8%;">UOM</th>
            <th style="width: 7%;">Qty</th>
            <th style="width: 10%;">Unit Price</th>
            <th style="width: 6%;">Rate%</th>
            <th style="width: 11%;">Value Excl. ST</th>
            <th style="width: 10%;">Sales Tax</th>
            <th style="width: 7%;">Extra Tax</th>
            <th style="width: 7%;">Further Tax</th>
            <th style="width: 7%;">FED</th>
            <th style="width: 7%;">Discount</th>
            <th style="width: 11%;">Total Value</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td class="item-center">{{ $loop->iteration }}</td>
                <td class="item-center">{{ $item->hs_code ?: 'N/A' }}</td>
                <td class="item-center">{{ $item->uom ?: 'N/A' }}</td>
                <td class="item-right">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="item-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="item-right">{{ $item->rate_percent !== null ? number_format((float) $item->rate_percent, 2).'%' : 'N/A' }}</td>
                <td class="item-right">{{ number_format($item->value_excluding_sales_tax, 2) }}</td>
                <td class="item-right">{{ number_format($item->sales_tax, 2) }}</td>
                <td class="item-right">{{ number_format($item->extra_tax, 2) }}</td>
                <td class="item-right">{{ number_format($item->further_tax, 2) }}</td>
                <td class="item-right">{{ number_format($item->fed_payable, 2) }}</td>
                <td class="item-right">{{ number_format($item->discount, 2) }}</td>
                <td class="item-right">{{ number_format($item->total_value, 2) }}</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="12">
                    <div class="item-desc">{{ $item->description ?: 'No description' }}</div>
                    <div class="item-subline">
                        Sale Type: {{ $item->sale_type ?: 'N/A' }}
                        @if($item->item_serial_number)
                            &nbsp; | &nbsp; Item Sr. No: {{ $item->item_serial_number }}
                        @endif
                        @if((float) $item->fixed_notified_value > 0)
                            &nbsp; | &nbsp; Fixed/Notified Value: {{ number_format($item->fixed_notified_value, 2) }}
                        @endif
                    </div>
                    <div class="item-subline">
                        SRO/Schedule No: {{ $item->sro_schedule_number ?: 'N/A' }}
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals-wrap">
        <div class="summary-box">
            <table class="summary-grid">
                <tr>
                    <td class="summary-label">Value of Sales Excl. ST:</td>
                    <td class="summary-value">{{ number_format($invoice->value_excluding_sales_tax, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Sales Tax:</td>
                    <td class="summary-value">{{ number_format($invoice->sales_tax_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Extra Tax:</td>
                    <td class="summary-value">{{ number_format($invoice->extra_tax_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Further Tax:</td>
                    <td class="summary-value">{{ number_format($invoice->further_tax_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">FED:</td>
                    <td class="summary-value">{{ number_format($invoice->fed_amount, 2) }}</td>
                </tr>
                <tr class="summary-discount">
                    <td class="summary-label">Discount:</td>
                    <td class="summary-value">- {{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                <tr class="summary-total">
                    <td class="summary-label">Grand Total:</td>
                    <td class="summary-value">{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($invoice->notes)
        <div class="notes-box">
            <div class="notes-title">Notes</div>
            {{ $invoice->notes }}
        </div>
    @endif

    <div class="footer-verify">
        <table class="footer-verify-table">
            <tr>
                <td class="footer-verify-left">
                    <strong>FBR Invoice ID:</strong> {{ $invoice->fbr_invoice_id ?: 'Pending submission' }}
                </td>
                <td class="footer-verify-right">
                    @if(!empty($qrCodeDataUri ?? null))
                        <table class="footer-verify-pack">
                            <tr>
                                <td class="qr-cell">
                                    <div class="verify-qr-box">
                                        <img src="{{ $qrCodeDataUri }}" width="72" height="72" alt="QR Code">
                                        <div class="verify-caption">Click to verify</div>
                                    </div>
                                </td>
                                <td>
                                    @if($fbrLogoDataUri)
                                        <img src="{{ $fbrLogoDataUri }}" class="fbr-logo-img" alt="FBR Digital Invoicing System">
                                    @endif
                                </td>
                            </tr>
                        </table>
                        @if($verificationDisplay)
                            <span class="verify-url">{{ $verificationDisplay }}</span>
                        @endif
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
