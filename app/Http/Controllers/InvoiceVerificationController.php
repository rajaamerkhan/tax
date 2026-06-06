<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\View\View;

class InvoiceVerificationController extends Controller
{
    public function __invoke(string $fbrInvoiceId): View
    {
        $invoice = Invoice::query()
            ->with(['customer', 'items', 'saleOriginProvince', 'destinationProvince', 'scenario'])
            ->where('fbr_invoice_id', $fbrInvoiceId)
            ->firstOrFail();

        return view('invoices.verify', compact('invoice'));
    }
}
