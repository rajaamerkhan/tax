<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Verification</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --card: #ffffff;
            --line: #e6e8f0;
            --text: #2f2b3d;
            --muted: #7a7691;
            --success-bg: rgba(40, 199, 111, .12);
            --success-text: #147d44;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Public Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 36px 20px 56px;
        }
        .hero, .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(47, 43, 61, .06);
        }
        .hero {
            padding: 28px;
            margin-bottom: 24px;
        }
        .kicker {
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }
        h1 {
            margin: 0;
            font-size: 1.9rem;
            line-height: 1.2;
        }
        .sub {
            margin-top: 10px;
            color: var(--muted);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            margin-top: 16px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--success-bg);
            color: var(--success-text);
            font-weight: 700;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        .card {
            padding: 22px 24px;
        }
        .card h2 {
            margin: 0 0 18px;
            font-size: 1.02rem;
        }
        dl {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 18px;
            margin: 0;
        }
        dt {
            font-size: .78rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }
        dd {
            margin: 0;
            font-weight: 600;
            overflow-wrap: anywhere;
        }
        .span-2 { grid-column: span 2; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .95rem;
        }
        thead th {
            text-align: left;
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            background: #f8f9fc;
        }
        tbody td {
            padding: 14px;
            border-bottom: 1px solid #f0f1f6;
            vertical-align: top;
        }
        tbody tr:last-child td { border-bottom: none; }
        .item-title { font-weight: 700; }
        .item-sub { margin-top: 4px; color: var(--muted); font-size: .88rem; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f1f6;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row.total {
            margin-top: 8px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            font-size: 1.08rem;
            font-weight: 800;
        }
        @media (max-width: 768px) {
            .grid, dl {
                grid-template-columns: 1fr;
            }
            .span-2 {
                grid-column: span 1;
            }
            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="hero">
            <div class="kicker">FBR Digital Invoice Verification</div>
            <h1>{{ $invoice->invoice_number }}</h1>
            <div class="sub">
                {{ $invoice->buyer_name }} | {{ $invoice->invoice_date?->format('d M Y') }} | FBR Invoice ID: {{ $invoice->fbr_invoice_id }}
            </div>
            <div class="badge">Invoice found in local verification registry</div>
        </section>

        <div class="grid">
            <section class="card">
                <h2>Invoice Details</h2>
                <dl>
                    <div>
                        <dt>Invoice Type</dt>
                        <dd>{{ $invoice->invoice_type ?: 'Sale Invoice' }}</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ ucfirst($invoice->status->value) }}</dd>
                    </div>
                    <div>
                        <dt>Scenario</dt>
                        <dd>{{ $invoice->scenario ? $invoice->scenario->code.' - '.$invoice->scenario->name : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt>Submitted At</dt>
                        <dd>{{ $invoice->fbr_submitted_at?->format('d M Y H:i') ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt>Origin Province</dt>
                        <dd>{{ $invoice->saleOriginProvince?->name ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt>Destination</dt>
                        <dd>{{ $invoice->destinationProvince?->name ?: 'N/A' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <h2>Buyer Information</h2>
                <dl>
                    <div>
                        <dt>Buyer</dt>
                        <dd>{{ $invoice->buyer_name ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt>Registration No.</dt>
                        <dd>{{ $invoice->buyer_ntn_cnic ?: 'N/A' }}</dd>
                    </div>
                    <div class="span-2">
                        <dt>Address</dt>
                        <dd>{{ $invoice->buyer_address ?: 'N/A' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="card" style="margin-bottom: 24px;">
            <h2>Invoice Items</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Rate %</th>
                        <th>Value Excl. ST</th>
                        <th>Sales Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="item-title">{{ $item->description ?: 'Untitled item' }}</div>
                                <div class="item-sub">{{ $item->hs_code ?: 'No HS code' }}</div>
                            </td>
                            <td>{{ $item->quantity }} {{ $item->uom }}</td>
                            <td>{{ $item->rate_percent !== null ? rtrim(rtrim(number_format((float) $item->rate_percent, 2, '.', ''), '0'), '.').'%' : 'N/A' }}</td>
                            <td>PKR {{ number_format($item->value_excluding_sales_tax, 2) }}</td>
                            <td>PKR {{ number_format($item->sales_tax, 2) }}</td>
                            <td>PKR {{ number_format($item->total_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Summary</h2>
            <div class="summary-row"><span>Value Excl. ST</span><strong>PKR {{ number_format($invoice->value_excluding_sales_tax, 2) }}</strong></div>
            <div class="summary-row"><span>Sales Tax</span><strong>PKR {{ number_format($invoice->sales_tax_amount, 2) }}</strong></div>
            <div class="summary-row"><span>Extra Tax</span><strong>PKR {{ number_format($invoice->extra_tax_amount, 2) }}</strong></div>
            <div class="summary-row"><span>Further Tax</span><strong>PKR {{ number_format($invoice->further_tax_amount, 2) }}</strong></div>
            <div class="summary-row"><span>FED</span><strong>PKR {{ number_format($invoice->fed_amount, 2) }}</strong></div>
            <div class="summary-row"><span>Discount</span><strong>PKR {{ number_format($invoice->discount_amount, 2) }}</strong></div>
            <div class="summary-row total"><span>Grand Total</span><strong>PKR {{ number_format($invoice->grand_total, 2) }}</strong></div>
        </section>
    </div>
</body>
</html>
