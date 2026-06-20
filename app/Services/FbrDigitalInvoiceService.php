<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\FbrApiLog;
use App\Models\Invoice;
use App\Support\FbrEnvironmentContext;
use App\Support\InvoicePayloadBuilder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

class FbrDigitalInvoiceService
{
    public function __construct(private readonly InvoicePayloadBuilder $payloadBuilder, private readonly FbrEnvironmentContext $environmentContext) {}

    public function validateInvoice(Invoice $invoice): array
    {
        return $this->sendInvoiceRequest($invoice, $this->invoiceEndpoint('validate_invoice'));
    }

    public function submitInvoice(Invoice $invoice): array
    {
        return $this->sendInvoiceRequest($invoice, $this->invoiceEndpoint('submit_invoice'));
    }

    public function fetchReferenceData(string $endpointKey, array $query = []): array
    {
        return $this->request()->get(config("fbr.endpoints.{$endpointKey}"), $query)->throw()->json();
    }

    public function verifyStAtl(string $registrationNumber, ?string $date = null): array
    {
        return $this->request()->get(config('fbr.endpoints.st_atl'), [
            'regno' => $registrationNumber,
            'date' => $date ?? now()->toDateString(),
        ])->throw()->json();
    }

    public function registrationType(string $registrationNumber): array
    {
        return $this->request()->get(config('fbr.endpoints.registration_type'), [
            'Registration_No' => $registrationNumber,
        ])->throw()->json();
    }

    private function request(): PendingRequest
    {
        $companyProfile = $this->companyProfile();
        $baseUrl = $this->currentEnvironment($companyProfile) === 'production' ? config('fbr.production_base_url') : config('fbr.sandbox_base_url');
        $token = $this->securityToken($companyProfile);

        $request = Http::acceptJson()
            ->asJson()
            ->baseUrl(rtrim((string) $baseUrl, '/'))
            ->timeout(30)
            ->retry(2, 1000);

        return filled($token) ? $request->withToken($token) : $request;
    }

    private function companyProfile(): ?CompanyProfile
    {
        return CompanyProfile::query()->first();
    }

    private function currentEnvironment(?CompanyProfile $companyProfile = null): string
    {
        return $companyProfile?->fbr_environment?->value ?? $this->environmentContext->current();
    }

    private function invoiceEndpoint(string $key): string
    {
        if ($this->currentEnvironment($this->companyProfile()) === 'production') {
            return (string) config("fbr.endpoints.{$key}_production", config("fbr.endpoints.{$key}"));
        }

        return (string) config("fbr.endpoints.{$key}");
    }

    private function securityToken(?CompanyProfile $companyProfile): ?string
    {
        $token = $this->usableToken($companyProfile?->fbr_token);

        if ($token !== null) {
            return $token;
        }

        return $this->usableToken(config('fbr.security_token', ''));
    }

    private function usableToken(mixed $token): ?string
    {
        $token = trim((string) $token);

        return in_array(strtolower($token), ['', 'n/a', 'na', 'null'], true) ? null : $token;
    }

    private function sendInvoiceRequest(Invoice $invoice, string $endpoint): array
    {
        $payload = $this->payloadBuilder->build($invoice);
        $log = FbrApiLog::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'endpoint' => $endpoint,
            'method' => 'POST',
            'environment' => $this->currentEnvironment($this->companyProfile()),
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
