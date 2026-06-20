<?php

namespace App\Http\Requests;

use App\Support\FbrEnvironmentContext;
use App\Support\PakistanTaxHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditInvoices() ?? false;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('invoices', 'invoice_number')
                    ->where('environment', app(FbrEnvironmentContext::class)->current())
                    ->ignore($this->route('invoice')),
            ],
            'invoice_date' => ['required', 'date'],
            'invoice_type' => ['required', 'string', 'in:Sale Invoice'],
            'scenario_id' => ['nullable', 'exists:scenarios,id'],
            'sale_origin_province_id' => ['nullable', 'exists:provinces,id'],
            'destination_province_id' => ['nullable', 'exists:provinces,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'buyer_ntn_cnic' => ['nullable', 'ntn'],
            'buyer_strn' => ['nullable', 'string', 'max:100'],
            'buyer_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.rate_percent' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.extra_tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.further_tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.fed_payable' => ['nullable', 'numeric', 'min:0'],
            'items.*.fixed_notified_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.hs_code_id' => ['nullable', 'exists:hs_codes,id'],
            'items.*.uom' => ['nullable', 'string', 'max:100'],
            'items.*.tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'items.*.sale_type_id' => ['nullable', 'exists:sale_types,id'],
            'items.*.sro_schedule_id' => ['nullable', 'exists:sro_schedules,id'],
            'items.*.sro_schedule_number' => ['nullable', 'string', 'max:100'],
            'items.*.item_serial_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'buyer_ntn_cnic' => $this->filled('buyer_ntn_cnic')
                ? PakistanTaxHelper::normalizeNtn((string) $this->input('buyer_ntn_cnic'))
                : $this->input('buyer_ntn_cnic'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);
                $grossValue = $quantity * $unitPrice;

                if ($discount > $grossValue) {
                    $validator->errors()->add(
                        "items.$index.discount",
                        'Discount cannot exceed Quantity x Per Unit Price for an item.'
                    );
                }
            }
        });
    }
}
