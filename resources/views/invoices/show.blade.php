@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="invoice-detail-page">
    <section class="invoice-detail-hero">
        <div class="invoice-detail-hero-main">
            <div class="invoice-detail-kicker">{{ config('app.name') }}</div>
            <h1>{{ $invoice->invoice_number }}</h1>
            <div class="invoice-detail-subtitle">
                <span>{{ $invoice->buyer_name ?: 'Buyer not set' }}</span>
                <span>{{ $invoice->invoice_date?->format('d M Y') ?: 'No invoice date' }}</span>
                <span>{{ $invoice->invoice_type ?: 'Sale Invoice' }}</span>
            </div>
            <div class="invoice-detail-badges">
                <span class="status-pill status-{{ $invoice->status->value }}">{{ ucfirst($invoice->status->value) }}</span>
                <span class="invoice-chip {{ $invoice->fbr_invoice_id ? 'is-success' : 'is-muted' }}">
                    {{ $invoice->fbr_invoice_id ? 'FBR Synced' : 'FBR Pending' }}
                </span>
                @if($invoice->isLocked())
                    <span class="invoice-chip is-danger">Locked</span>
                @elseif($invoice->editable_until)
                    <span class="invoice-chip is-warning">
                        Editable until {{ $invoice->editable_until->format('d M Y H:i') }}
                    </span>
                @endif
            </div>
        </div>
        <div class="invoice-detail-hero-actions">
            @if(auth()->user()?->canEditInvoices())
                @if(! $invoice->isLocked())
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline-light">Edit Invoice</a>
                @endif
                <form method="POST" action="{{ route('invoices.validate-fbr', $invoice) }}">
                    @csrf
                    <button class="btn btn-outline-light">Validate with FBR</button>
                </form>
                <form method="POST" action="{{ route('invoices.submit-fbr', $invoice) }}">
                    @csrf
                    <button class="btn btn-primary">Submit to FBR</button>
                </form>
            @endif
            <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-light">Print</a>
            <a href="{{ route('invoices.download-pdf', $invoice) }}" class="btn btn-outline-light">Download PDF</a>
        </div>
    </section>

    <div class="invoice-detail-layout">
        <div class="invoice-detail-main">
            <section class="invoice-detail-meta-grid">
                <article class="clean-form-card">
                    <div class="clean-card-title">Buyer Information</div>
                    <div class="clean-card-body">
                        <dl class="invoice-detail-facts">
                            <div>
                                <dt>Customer</dt>
                                <dd>{{ $invoice->buyer_name ?: 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>Registration No.</dt>
                                <dd>{{ $invoice->buyer_ntn_cnic ?: 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>Buyer Type</dt>
                                <dd>{{ $invoice->customer?->buyer_type?->value ? ucfirst($invoice->customer->buyer_type->value) : 'N/A' }}</dd>
                            </div>
                            <div class="span-2">
                                <dt>Buyer Address</dt>
                                <dd>{{ $invoice->buyer_address ?: 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>

                <article class="clean-form-card">
                    <div class="clean-card-title">Invoice Details</div>
                    <div class="clean-card-body">
                        <dl class="invoice-detail-facts">
                            <div>
                                <dt>Invoice Type</dt>
                                <dd>{{ $invoice->invoice_type ?: 'Sale Invoice' }}</dd>
                            </div>
                            <div>
                                <dt>Scenario</dt>
                                <dd>{{ $invoice->scenario ? $invoice->scenario->code.' - '.$invoice->scenario->name : 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>Sale Origin Province</dt>
                                <dd>{{ $invoice->saleOriginProvince?->name ?: 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>Destination of Supply</dt>
                                <dd>{{ $invoice->destinationProvince?->name ?: 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt>FBR Invoice ID</dt>
                                <dd>{{ $invoice->fbr_invoice_id ?: 'Pending' }}</dd>
                            </div>
                            <div>
                                <dt>Submitted At</dt>
                                <dd>{{ $invoice->fbr_submitted_at?->format('d M Y H:i') ?: 'Not submitted' }}</dd>
                            </div>
                            <div>
                                <dt>Editable Until</dt>
                                <dd>
                                    @if($invoice->editable_until)
                                        <span class="countdown" data-until="{{ $invoice->editable_until->toIso8601String() }}">
                                            {{ $invoice->editable_until->format('d M Y H:i') }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>Locked At</dt>
                                <dd>{{ $invoice->locked_at?->format('d M Y H:i') ?: 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            </section>

            <section class="clean-form-card">
                <div class="clean-card-toolbar">
                    <div>
                        <h2 class="clean-card-title">Invoice Items</h2>
                        <p class="invoice-detail-section-note">Line items, taxes, overrides, and totals captured on this invoice.</p>
                    </div>
                    <div class="invoice-detail-count">{{ $invoice->items->count() }} item{{ $invoice->items->count() === 1 ? '' : 's' }}</div>
                </div>
                <div class="clean-card-body pt-3">
                    <div class="table-responsive invoice-detail-table-wrap">
                        <table class="table invoice-detail-table align-middle">
                            <thead>
                            <tr>
                                <th>Item</th>
                                <th>Sale Type</th>
                                <th>Qty / UOM</th>
                                <th>Rate</th>
                                <th>Fixed/Notified Value</th>
                                <th>Value Excl. ST</th>
                                <th>Sales Tax</th>
                                <th>Adjustments</th>
                                <th class="text-end">Total Value</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td>
                                        <div class="invoice-item-primary">{{ $item->description ?: 'Untitled item' }}</div>
                                        <div class="invoice-item-secondary">{{ $item->hs_code ?: 'No HS code' }}</div>
                                        @if($item->item_serial_number || $item->sro_schedule_number)
                                            <div class="invoice-item-meta">
                                                @if($item->item_serial_number)
                                                    <span>Sr. {{ $item->item_serial_number }}</span>
                                                @endif
                                                @if($item->sro_schedule_number)
                                                    <span>SRO {{ $item->sro_schedule_number }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item->sale_type ?: 'N/A' }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 4, '.', ''), '0'), '.') ?: '0' }} {{ $item->uom ?: '' }}</td>
                                    <td>
                                        <div>{{ $item->rate_percent !== null ? rtrim(rtrim(number_format((float) $item->rate_percent, 2, '.', ''), '0'), '.').'%' : 'N/A' }}</div>
                                    </td>
                                    <td>PKR {{ number_format((float) $item->fixed_notified_value, 2) }}</td>
                                    <td>PKR {{ number_format($item->value_excluding_sales_tax, 2) }}</td>
                                    <td>PKR {{ number_format($item->sales_tax, 2) }}</td>
                                    <td>
                                        <div class="invoice-item-adjustments">
                                            <span>Extra: PKR {{ number_format($item->extra_tax, 2) }}</span>
                                            <span>Further: PKR {{ number_format($item->further_tax, 2) }}</span>
                                            <span>FED: PKR {{ number_format($item->fed_payable, 2) }}</span>
                                            <span class="text-danger">Discount: PKR {{ number_format($item->discount, 2) }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end invoice-item-total">PKR {{ number_format($item->total_value, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="invoice-detail-bottom-grid">
                <article class="clean-form-card">
                    <div class="clean-card-title">Notes</div>
                    <div class="clean-card-body">
                        <div class="invoice-detail-note-box">
                            {{ $invoice->notes ?: 'No notes were added to this invoice.' }}
                        </div>
                    </div>
                </article>

                <article class="clean-form-card">
                    <div class="clean-card-title">FBR Response</div>
                    <div class="clean-card-body">
                        @if($invoice->error_message)
                            <div class="alert alert-danger mb-3">{{ $invoice->error_message }}</div>
                        @endif

                        <pre class="json-box invoice-detail-json">{{ json_encode($invoice->fbr_response_json ?? ['message' => 'No FBR response recorded yet.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </article>
            </section>
        </div>

        <aside class="invoice-detail-sidebar">
            <section class="clean-summary-card">
                <div class="clean-summary-head">Invoice Summary</div>
                <div class="clean-summary-body">
                    <div><span>Value Excl. ST</span><strong>PKR {{ number_format($invoice->value_excluding_sales_tax, 2) }}</strong></div>
                    <div><span>Sales Tax</span><strong>PKR {{ number_format($invoice->sales_tax_amount, 2) }}</strong></div>
                    <div><span>Extra Tax</span><strong>PKR {{ number_format($invoice->extra_tax_amount, 2) }}</strong></div>
                    <div><span>Further Tax</span><strong>PKR {{ number_format($invoice->further_tax_amount, 2) }}</strong></div>
                    <div><span>FED</span><strong>PKR {{ number_format($invoice->fed_amount, 2) }}</strong></div>
                    <div class="discount-line"><span>Discount</span><strong>PKR {{ number_format($invoice->discount_amount, 2) }}</strong></div>
                    <div class="grand-total-line"><span>Grand Total</span><strong>PKR {{ number_format($invoice->grand_total, 2) }}</strong></div>
                </div>
            </section>

            <section class="clean-form-card invoice-detail-sidebar-card">
                <div class="clean-card-title">Submission Status</div>
                <div class="clean-card-body">
                    <dl class="invoice-detail-facts single-column">
                        <div>
                            <dt>Current Status</dt>
                            <dd>{{ ucfirst($invoice->status->value) }}</dd>
                        </div>
                        <div>
                            <dt>Created</dt>
                            <dd>{{ $invoice->created_at?->format('d M Y H:i') ?: 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>Last Updated</dt>
                            <dd>{{ $invoice->updated_at?->format('d M Y H:i') ?: 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>PDF Artifact</dt>
                            <dd>{{ $invoice->pdf_path ? 'Generated' : 'Not generated' }}</dd>
                        </div>
                        <div>
                            <dt>QR Artifact</dt>
                            <dd>{{ $invoice->qr_code_path ? 'Generated' : 'Not generated' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
