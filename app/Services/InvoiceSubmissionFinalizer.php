<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class InvoiceSubmissionFinalizer
{
    public function finalize(Invoice $invoice, array $response): Invoice
    {
        $invoice->refresh();

        $fbrInvoiceId = (string) Arr::get($response, 'invoiceNumber', Arr::get($response, 'invoiceId', ''));

        $invoice->markSubmitted($response, $fbrInvoiceId !== '' ? $fbrInvoiceId : null);
        $invoice->refresh()->loadMissing(['items', 'customer', 'saleOriginProvince', 'destinationProvince']);

        $qrCodeService = app(InvoiceQrCodeService::class);
        $qrPath = $qrCodeService->store($invoice);
        $qrCodeDataUri = $qrCodeService->dataUri($invoice);
        $verificationUrl = $qrCodeService->verificationUrl($invoice);

        $pdfPath = 'invoices/invoice-'.$invoice->invoice_number.'.pdf';
        $pdfBinary = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeDataUri' => $qrCodeDataUri,
            'verificationUrl' => $verificationUrl,
        ])
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
