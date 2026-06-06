<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InvoiceDraftImport implements ToCollection, WithHeadingRow
{
    public Collection $rows;

    public function collection(Collection $collection): void
    {
        $this->rows = $collection->map(fn ($row) => [
            'invoice_number' => $row['invoice_number'] ?? $row['invoice_no'] ?? null,
            'invoice_date' => $row['invoice_date'] ?? $row['date'] ?? null,
            'buyer_name' => $row['buyer_name'] ?? null,
            'buyer_ntn_cnic' => $row['buyer_ntn_cnic'] ?? $row['ntn_cnic'] ?? null,
            'buyer_strn' => $row['buyer_strn'] ?? $row['strn'] ?? null,
            'invoice_type' => $row['invoice_type'] ?? 'Sale Invoice',
            'item_description' => $row['description'] ?? $row['item_description'] ?? null,
            'quantity' => $row['quantity'] ?? 0,
            'unit_price' => $row['unit_price'] ?? 0,
            'rate_percent' => $row['rate_percent'] ?? $row['rate'] ?? 0,
            'hs_code' => $row['hs_code'] ?? null,
            'uom' => $row['uom'] ?? null,
        ]);
    }
}
