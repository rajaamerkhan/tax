<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceSubmissionFinalizer
{
    public function finalize(Invoice $invoice, array $response): Invoice
    {
        $invoice->refresh();

        $fbrInvoiceId = (string) Arr::get($response, 'invoiceNumber', Arr::get($response, 'invoiceId', ''));
        $verifyUrl = rtrim((string) config('fbr.verify_url'), '/');
        $qrPayload = $verifyUrl !== '' && $fbrInvoiceId !== ''
            ? $verifyUrl.'/'.$fbrInvoiceId
            : $invoice->invoice_number;

        $qrSvg = QrCode::format('svg')->size(180)->generate($qrPayload);
        $qrPath = 'qrcodes/invoice-'.$invoice->id.'.svg';
        Storage::disk('public')->put($qrPath, $qrSvg);

        $invoice->markSubmitted($response, $fbrInvoiceId !== '' ? $fbrInvoiceId : null);
        $invoice->refresh()->loadMissing(['items', 'customer', 'saleOriginProvince', 'destinationProvince']);

        $pdfPath = 'invoices/invoice-'.$invoice->invoice_number.'.pdf';
        $pdfBinary = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4')
            ->output();
        Storage::disk('public')->put($pdfPath, $pdfBinary);

        $invoice->forceFill([
            'qr_code_path' => $qrPath,
            'pdf_path' => $pdfPath,
        ])->save();

        app(AuditLogger::class)->log('invoice.fbr_submit_success', $invoice->fresh(), null, $response);

        return $invoice->fresh(['customer', 'items']);
    }
}
