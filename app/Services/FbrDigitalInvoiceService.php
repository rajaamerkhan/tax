<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\FbrApiLog;
use App\Models\Invoice;
use App\Support\InvoicePayloadBuilder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

class FbrDigitalInvoiceService
{
    public function __construct(private readonly InvoicePayloadBuilder $payloadBuilder)
    {
    }

    public function validateInvoice(Invoice $invoice): array
    {
        return $this->sendInvoiceRequest($invoice, config('fbr.endpoints.validate_invoice'));
    }

    public function submitInvoice(Invoice $invoice): array
    {
        return $this->sendInvoiceRequest($invoice, config('fbr.endpoints.submit_invoice'));
    }

    public function fetchReferenceData(string $endpointKey, array $query = []): array
    {
        return $this->request()->get(config("fbr.endpoints.{$endpointKey}"), $query)->throw()->json();
    }

    public function verifyStAtl(string $registrationNumber): array
    {
        return $this->request()->get(config('fbr.endpoints.st_atl'), [
            'registration_no' => $registrationNumber,
        ])->throw()->json();
    }

    private function request(): PendingRequest
    {
        $companyProfile = CompanyProfile::query()->first();
        $environment = $companyProfile?->fbr_environment?->value ?? config('fbr.env');
        $baseUrl = $environment === 'production' ? config('fbr.production_base_url') : config('fbr.sandbox_base_url');
        $token = $companyProfile?->fbr_token ?: config('fbr.security_token');

        return Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->baseUrl(rtrim((string) $baseUrl, '/'))
            ->timeout(30)
            ->retry(2, 1000);
    }

    private function sendInvoiceRequest(Invoice $invoice, string $endpoint): array
    {
        $payload = $this->payloadBuilder->build($invoice);
        $log = FbrApiLog::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'endpoint' => $endpoint,
            'method' => 'POST',
            'environment' => config('fbr.env'),
            'status' => 'pending',
            'request_payload' => $payload,
        ]);

        try {
            $response = $this->request()->post($endpoint, $payload);
            $response->throw();
            $json = $response->json();

            $log->update([
                'http_status' => $response->status(),
                'status' => data_get($json, 'validationResponse.status', 'success'),
                'response_payload' => $json,
            ]);

            return $json;
        } catch (Throwable $exception) {
            $response = method_exists($exception, 'response') ? $exception->response : null;

            $log->update([
                'http_status' => $response?->status(),
                'status' => 'failed',
                'response_payload' => $response?->json(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
