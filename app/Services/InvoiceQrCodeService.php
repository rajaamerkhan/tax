<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceQrCodeService
{
    public function payload(Invoice $invoice): string
    {
        $verifyUrl = $this->baseVerifyUrl();

        if ($verifyUrl !== '' && $invoice->fbr_invoice_id) {
            return $verifyUrl.'/'.$invoice->fbr_invoice_id;
        }

        return $invoice->invoice_number;
    }

    public function verificationUrl(Invoice $invoice): ?string
    {
        $verifyUrl = $this->baseVerifyUrl();

        if ($verifyUrl === '' || ! $invoice->fbr_invoice_id) {
            return null;
        }

        return $verifyUrl.'/'.$invoice->fbr_invoice_id;
    }

    public function svgMarkup(Invoice $invoice, int $size = 180): string
    {
        return QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->generate($this->payload($invoice));
    }

    public function dataUri(Invoice $invoice, int $size = 180): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svgMarkup($invoice, $size));
    }

    public function store(Invoice $invoice, int $size = 180): string
    {
        $path = 'qrcodes/invoice-'.$invoice->id.'.svg';

        Storage::disk('public')->put($path, $this->svgMarkup($invoice, $size));

        return $path;
    }

    private function baseVerifyUrl(): string
    {
        $configuredUrl = rtrim((string) config('fbr.verify_url'), '/');
        $request = request();

        if ($request && ! $this->isLocalUrl($request->root()) && ($configuredUrl === '' || $this->isLocalUrl($configuredUrl))) {
            return rtrim($request->root(), '/').'/invoices/verify';
        }

        return $configuredUrl;
    }

    private function isLocalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)
            || str_ends_with((string) $host, '.localhost');
    }
}
