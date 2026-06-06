@php($oldItems = old('items', $invoice->items->count() ? $invoice->items->map(fn($item) => [
    'description' => $item->description,
    'hs_code_id' => $item->hs_code_id,
    'uom' => $item->uom ?: $item->uomRelation?->name ?: $item->uomRelation?->code,
    'tax_rate_id' => $item->tax_rate_id,
    'sale_type_id' => $item->sale_type_id,
    'sro_schedule_id' => $item->sro_schedule_id,
    'quantity' => $item->quantity,
    'unit_price' => $item->unit_price,
    'rate_percent' => $item->rate_percent,
    'discount' => $item->discount,
    'extra_tax' => $item->extra_tax,
    'further_tax' => $item->further_tax,
    'fed_payable' => $item->fed_payable,
    'fixed_notified_value' => $item->fixed_notified_value,
    'item_serial_number' => $item->item_serial_number,
    'total_value' => $item->total_value,
    'hs_code' => $item->hs_code,
    'sale_type' => $item->sale_type,
    'sro_schedule_number' => $item->sro_schedule_number,
])->toArray() : [[
    'description' => '',
    'quantity' => 1,
    'unit_price' => 0,
    'rate_percent' => 18,
    'discount' => 0,
    'extra_tax' => 0,
    'further_tax' => 0,
    'fed_payable' => 0,
    'fixed_notified_value' => 0,
]]))

<div class="invoice-clean-page">
    <div class="invoice-clean-grid">
        <section class="clean-form-card">
            <div class="clean-card-title">Buyer Information</div>
            <div class="clean-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Buyer Name</label>
                        <select class="form-select select2-ajax" name="customer_id" data-autocomplete-url="{{ route('invoices.autocomplete', 'customers') }}" data-placeholder="Search for a buyer">
                            <option value=""></option>
                            @if($selectedCustomer)
                                <option value="{{ $selectedCustomer->id }}" selected data-name="{{ $selectedCustomer->name }}" data-ntn="{{ $selectedCustomer->ntn_cnic }}" data-strn="{{ $selectedCustomer->strn }}" data-address="{{ $selectedCustomer->address }}" data-buyer-type="{{ ucfirst(optional($selectedCustomer->buyer_type)->value ?? 'unregistered') }}" data-province-id="{{ $selectedCustomer->province_id }}">{{ $selectedCustomer->name }}{{ $selectedCustomer->ntn_cnic ? ' | '.$selectedCustomer->ntn_cnic : '' }}</option>
                            @endif
                        </select>
                        <input type="hidden" class="buyer-field" name="buyer_name" value="{{ old('buyer_name', $invoice->buyer_name ?: $selectedCustomer?->name) }}">
                        <input type="hidden" class="buyer-field" name="buyer_strn" value="{{ old('buyer_strn', $invoice->buyer_strn ?: $selectedCustomer?->strn) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buyer Registration No. (CNIC/NTN)</label>
                        <input class="form-control buyer-field" name="buyer_ntn_cnic" value="{{ old('buyer_ntn_cnic', $invoice->buyer_ntn_cnic ?: $selectedCustomer?->ntn_cnic) }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Buyer Type</label>
                        <input class="form-control" name="buyer_type_display" value="{{ old('buyer_type_display', $selectedCustomer ? ucfirst(optional($selectedCustomer->buyer_type)->value ?? 'unregistered') : '') }}" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Buyer Address</label>
                        <textarea class="form-control buyer-field" name="buyer_address" rows="3" readonly>{{ old('buyer_address', $invoice->buyer_address ?: $selectedCustomer?->address) }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="clean-form-card">
            <div class="clean-card-title">Invoice Details</div>
            <div class="clean-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Invoice Type</label>
                        <select class="form-select select2-basic" name="invoice_type" data-placeholder="Select invoice type">
                            <option value="Sale Invoice" @selected(old('invoice_type', $invoice->invoice_type) === 'Sale Invoice')>Sale Invoice</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Invoice Ref No.</label>
                        <input class="form-control" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Invoice Date</label>
                        <input type="date" class="form-control" name="invoice_date" value="{{ old('invoice_date', optional($invoice->invoice_date)->format('Y-m-d') ?: $invoice->invoice_date) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scenario</label>
                        <select class="form-select select2-basic" name="scenario_id" data-placeholder="Select scenario">
                            <option value="">-- Select Scenario --</option>
                            @foreach($invoiceScenarioOptions as $scenarioOption)
                                <option value="{{ $scenarioOption->id }}" @selected((string) old('scenario_id', $invoice->scenario_id) === (string) $scenarioOption->id)>{{ $scenarioOption->code }} - {{ $scenarioOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Sale Origin Province</label>
                        <select class="form-select select2-basic" name="sale_origin_province_id" data-placeholder="Select province">
                            <option value="">-- Select Province --</option>
                            @foreach($invoiceProvinceOptions as $provinceOption)
                                <option value="{{ $provinceOption['id'] }}" @selected((string) old('sale_origin_province_id', $invoice->sale_origin_province_id) === (string) $provinceOption['id'])>{{ $provinceOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Destination of Supply</label>
                        <select class="form-select select2-basic" name="destination_province_id" data-placeholder="Select province">
                            <option value="">-- Select Province --</option>
                            @foreach($invoiceDestinationOptions as $provinceOption)
                                <option value="{{ $provinceOption['id'] }}" @selected((string) old('destination_province_id', $invoice->destination_province_id) === (string) $provinceOption['id'])>{{ $provinceOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="clean-form-card">
        <div class="clean-card-toolbar">
            <div class="clean-card-title mb-0">Invoice Items</div>
            <button type="button" class="btn btn-primary" id="add-item-row"><i class="bi bi-plus-circle"></i> Add Item</button>
        </div>
        <div class="clean-card-body">
            <div class="clean-item-list" id="invoice-items-list">
                @foreach($oldItems as $index => $item)
                    @php($selectedHsCode = $hsCodes->firstWhere('id', $item['hs_code_id'] ?? null))
                    @php($selectedSaleType = $saleTypes->firstWhere('id', $item['sale_type_id'] ?? null))
                    @php($selectedTaxRate = $taxRates->firstWhere('id', $item['tax_rate_id'] ?? null) ?? $taxRates->firstWhere('rate', (float) ($item['rate_percent'] ?? 18)))
                    <div class="clean-item-card">
                        <div class="clean-item-card-head">
                            <div class="clean-item-card-title">Item <span class="row-number">{{ $loop->iteration }}</span></div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary duplicate-row">Duplicate</button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row">Delete Row</button>
                            </div>
                        </div>
                        <div class="clean-item-grid">
                            <div class="clean-item-field">
                                <label class="form-label">Item HS Code</label>
                                <select class="form-select hs-code-select select2-ajax" name="items[{{ $index }}][hs_code_id]" data-autocomplete-url="{{ route('invoices.autocomplete', 'hs-codes') }}" data-placeholder="Search HS code">
                                    <option value=""></option>
                                    @if($selectedHsCode)
                                        <option value="{{ $selectedHsCode->id }}" selected data-description="{{ $selectedHsCode->description }}" data-code="{{ $selectedHsCode->code }}">{{ $selectedHsCode->code }} - {{ $selectedHsCode->description }}</option>
                                    @endif
                                </select>
                                <input type="hidden" name="items[{{ $index }}][hs_code]" value="{{ $item['hs_code'] ?? '' }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Description</label>
                                <input class="form-control" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" placeholder="Item description">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Sale Type</label>
                                <select class="form-select sale-type-select select2-basic" name="items[{{ $index }}][sale_type_id]" data-placeholder="Select sale type">
                                    <option value="">Select Sale Type</option>
                                    @foreach($saleTypes as $saleTypeOption)
                                        <option value="{{ $saleTypeOption->id }}" data-name="{{ $saleTypeOption->name }}" @selected(($item['sale_type_id'] ?? null) == $saleTypeOption->id)>{{ $saleTypeOption->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="items[{{ $index }}][sale_type]" value="{{ $item['sale_type'] ?? '' }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Rate %</label>
                                <select class="form-select tax-rate-select select2-basic" name="items[{{ $index }}][tax_rate_id]" data-placeholder="Select rate">
                                    <option value="">Select rate</option>
                                    @foreach($taxRates as $taxRate)
                                        <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}" @selected($selectedTaxRate?->id == $taxRate->id)>{{ $taxRate->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">UOM</label>
                                <select class="form-select uom-select select2-basic" name="items[{{ $index }}][uom]" data-placeholder="Select UOM">
                                    <option value="">Select UOM</option>
                                    @foreach($uomOptions as $uomOption)
                                        <option value="{{ $uomOption }}" data-name="{{ $uomOption }}" data-code="{{ $uomOption }}" @selected(($item['uom'] ?? null) === $uomOption)>{{ $uomOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Quantity</label>
                                <input step="0.001" class="form-control calc-field quantity" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Per Unit Price</label>
                                <input step="0.01" class="form-control calc-field unit-price" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Value Excl. Tax</label>
                                <input readonly class="form-control value-excl-tax" value="">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Sales Tax</label>
                                <input readonly class="form-control sales-tax-field" value="">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Fixed/Notified Value</label>
                                <input step="0.01" class="form-control calc-field" name="items[{{ $index }}][fixed_notified_value]" value="{{ $item['fixed_notified_value'] ?? 0 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Extra Tax</label>
                                <input step="0.01" class="form-control calc-field" name="items[{{ $index }}][extra_tax]" value="{{ $item['extra_tax'] ?? 0 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Further Tax</label>
                                <input step="0.01" class="form-control calc-field" name="items[{{ $index }}][further_tax]" value="{{ $item['further_tax'] ?? 0 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">FED Payable</label>
                                <input step="0.01" class="form-control calc-field" name="items[{{ $index }}][fed_payable]" value="{{ $item['fed_payable'] ?? 0 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Discount</label>
                                <input step="0.01" class="form-control calc-field discount" name="items[{{ $index }}][discount]" value="{{ $item['discount'] ?? 0 }}">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Total Value</label>
                                <div class="clean-total-box line-total">{{ isset($item['total_value']) ? number_format((float) $item['total_value'], 2) : '0.00' }}</div>
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">SRO/Schedule No.</label>
                                <input class="form-control" name="items[{{ $index }}][sro_schedule_number]" value="{{ $item['sro_schedule_number'] ?? '' }}" placeholder="SRO No.">
                            </div>
                            <div class="clean-item-field">
                                <label class="form-label">Item Sr. No.</label>
                                <input class="form-control" name="items[{{ $index }}][item_serial_number]" value="{{ $item['item_serial_number'] ?? '' }}" placeholder="Serial No.">
                            </div>
                        </div>
                        <input type="hidden" class="calc-field rate-percent" name="items[{{ $index }}][rate_percent]" value="{{ $item['rate_percent'] ?? ($selectedTaxRate?->rate ?? 18) }}">
                        <input type="hidden" name="items[{{ $index }}][sro_schedule_id]" value="{{ $item['sro_schedule_id'] ?? '' }}">
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="invoice-clean-footer-grid">
        <section class="clean-form-card">
            <div class="clean-card-title">Notes</div>
            <div class="clean-card-body">
                <textarea class="form-control" name="notes" rows="4" placeholder="Additional notes...">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
        </section>

        <aside class="clean-summary-card">
            <div class="clean-summary-head">Summary</div>
            <div class="clean-summary-body">
                <div><span>Value of Sales Excl. ST:</span><strong id="sum-base">0.00</strong></div>
                <div><span>Sales Tax:</span><strong id="sum-tax">0.00</strong></div>
                <div><span>Extra Tax:</span><strong id="sum-extra-tax">0.00</strong></div>
                <div><span>Further Tax:</span><strong id="sum-further-tax">0.00</strong></div>
                <div><span>FED:</span><strong id="sum-fed">0.00</strong></div>
                <div class="discount-line"><span>Discount:</span><strong id="sum-discount">0.00</strong></div>
                <div class="grand-total-line"><span>Grand Total:</span><strong id="sum-grand">0.00</strong></div>
                <button class="btn btn-success clean-save-button"><i class="bi bi-floppy"></i> Save Invoice</button>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
window.invoiceFormConfig = {
    referenceOptionsUrl: @json(route('invoices.reference-options')),
    select2MinimumInputLength: 0,
    rowTemplate: `
    <div class="clean-item-card">
        <div class="clean-item-card-head">
            <div class="clean-item-card-title">Item <span class="row-number"></span></div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary duplicate-row">Duplicate</button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">Delete Row</button>
            </div>
        </div>
        <div class="clean-item-grid">
            <div class="clean-item-field">
                <label class="form-label">Item HS Code</label>
                <select class="form-select hs-code-select select2-ajax" name="__NAME__[hs_code_id]" data-autocomplete-url="{{ route('invoices.autocomplete', 'hs-codes') }}" data-placeholder="Search HS code"><option value=""></option></select>
                <input type="hidden" name="__NAME__[hs_code]">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Description</label>
                <input class="form-control" name="__NAME__[description]" placeholder="Item description">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Sale Type</label>
                <select class="form-select sale-type-select select2-basic" name="__NAME__[sale_type_id]" data-placeholder="Select sale type">
                    <option value="">Select Sale Type</option>
                    @foreach($saleTypes as $saleTypeOption)
                        <option value="{{ $saleTypeOption->id }}" data-name="{{ $saleTypeOption->name }}">{{ $saleTypeOption->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="__NAME__[sale_type]" value="">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Rate %</label>
                <select class="form-select tax-rate-select select2-basic" name="__NAME__[tax_rate_id]" data-placeholder="Select rate">
                    <option value="">Select rate</option>
                    @foreach($taxRates as $taxRate)
                        <option value="{{ $taxRate->id }}" data-rate="{{ $taxRate->rate }}">{{ $taxRate->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="clean-item-field">
                <label class="form-label">UOM</label>
                <select class="form-select uom-select select2-basic" name="__NAME__[uom]" data-placeholder="Select UOM">
                    <option value="">Select UOM</option>
                    @foreach($uomOptions as $uomOption)
                        <option value="{{ $uomOption }}" data-name="{{ $uomOption }}" data-code="{{ $uomOption }}">{{ $uomOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="clean-item-field">
                <label class="form-label">Quantity</label>
                <input step="0.001" class="form-control calc-field quantity" name="__NAME__[quantity]" value="1">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Per Unit Price</label>
                <input step="0.01" class="form-control calc-field unit-price" name="__NAME__[unit_price]" value="0">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Value Excl. Tax</label>
                <input readonly class="form-control value-excl-tax" value="">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Sales Tax</label>
                <input readonly class="form-control sales-tax-field" value="">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Fixed/Notified Value</label>
                <input step="0.01" class="form-control calc-field" name="__NAME__[fixed_notified_value]" value="0">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Extra Tax</label>
                <input step="0.01" class="form-control calc-field" name="__NAME__[extra_tax]" value="0">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Further Tax</label>
                <input step="0.01" class="form-control calc-field" name="__NAME__[further_tax]" value="0">
            </div>
            <div class="clean-item-field">
                <label class="form-label">FED Payable</label>
                <input step="0.01" class="form-control calc-field" name="__NAME__[fed_payable]" value="0">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Discount</label>
                <input step="0.01" class="form-control calc-field discount" name="__NAME__[discount]" value="0">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Total Value</label>
                <div class="clean-total-box line-total">0.00</div>
            </div>
            <div class="clean-item-field">
                <label class="form-label">SRO/Schedule No.</label>
                <input class="form-control" name="__NAME__[sro_schedule_number]" value="" placeholder="SRO No.">
            </div>
            <div class="clean-item-field">
                <label class="form-label">Item Sr. No.</label>
                <input class="form-control" name="__NAME__[item_serial_number]" value="" placeholder="Serial No.">
            </div>
        </div>
        <input type="hidden" class="calc-field rate-percent" name="__NAME__[rate_percent]" value="18">
        <input type="hidden" name="__NAME__[sro_schedule_id]" value="">
    </div>`
};
</script>
@endpush
