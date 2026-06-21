<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\HsCode;
use App\Models\Invoice;
use App\Models\Province;
use App\Models\SaleType;
use App\Models\Scenario;
use App\Models\TaxRate;
use App\Models\Uom;
use App\Services\FbrDigitalInvoiceService;
use App\Services\InvoiceSubmissionFinalizer;
use App\Support\FbrDemoScenarioFixtures;
use App\Support\FbrEnvironmentContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class DemoInvoiceController extends Controller
{
    public function __construct(
        private readonly FbrEnvironmentContext $environmentContext,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, FbrDigitalInvoiceService $service, InvoiceSubmissionFinalizer $finalizer): RedirectResponse
    {
        $clientId = $this->tenantContext->clientId($request->user());
        $company = CompanyProfile::query()->where('client_id', $clientId)->firstOrFail();

        if ($company->fbr_environment?->value !== 'sandbox') {
            return redirect()->route('admin.mock-fbr-console')
                ->with('error', 'Demo invoice submission is blocked unless Company Profile is set to Sandbox.');
        }

        $request->validate([
            'scenario_code' => ['required', 'string'],
        ]);

        $fixture = FbrDemoScenarioFixtures::fixtureFor($company, $request->string('scenario_code')->toString());

        if (! $fixture) {
            return redirect()->route('admin.mock-fbr-console')
                ->with('error', 'No sandbox demo fixture is available for the selected scenario.');
        }

        $scenario = Scenario::query()->firstOrCreate(
            ['code' => $fixture['scenario_code']],
            ['name' => $fixture['scenario_name'], 'document_type_id' => '1'],
        );
        $originProvince = Province::query()->find($company->province_id) ?? Province::query()->first();
        $destinationProvince = Province::query()->where('name', 'Sindh')->first()
            ?? Province::query()->whereKeyNot($originProvince?->id)->first()
            ?? $originProvince;
        $customer = Customer::query()->updateOrCreate(
            ['client_id' => $clientId, 'name' => $fixture['buyer_name']],
            [
                'client_id' => $clientId,
                'ntn_cnic' => $fixture['buyer_ntn_cnic'],
                'strn' => $fixture['buyer_strn'],
                'buyer_type' => $fixture['buyer_type'],
                'province_id' => $destinationProvince?->id,
                'address' => $fixture['buyer_address'],
                'status' => 'active',
            ],
        );

        $uom = Uom::query()->firstOrCreate(
            ['name' => $fixture['uom']],
            ['code' => $fixture['uom'], 'fbr_id' => null],
        );
        $hsCode = HsCode::query()->firstOrCreate(
            ['code' => $fixture['hs_code']],
            [
                'description' => $fixture['description'],
                'uom_id' => $uom->id,
                'is_active' => true,
            ],
        );
        $taxRate = TaxRate::query()->firstOrCreate(
            ['name' => $fixture['rate_label']],
            ['rate' => $fixture['rate_percent'], 'is_active' => true],
        );
        $saleType = SaleType::query()->firstOrCreate(
            ['name' => $fixture['sale_type']],
            ['code' => $fixture['sale_type_code'], 'fbr_id' => null],
        );

        $invoice = Invoice::create([
            'client_id' => $clientId,
            'invoice_number' => 'MOCK-'.Str::upper(Str::random(8)),
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'Sale Invoice',
            'environment' => $this->environmentContext->current(),
            'scenario_id' => $scenario?->id,
            'sale_origin_province_id' => $originProvince?->id,
            'destination_province_id' => $destinationProvince?->id,
            'customer_id' => $customer->id,
            'buyer_name' => $customer->name,
            'buyer_ntn_cnic' => $customer->ntn_cnic,
            'buyer_strn' => $customer->strn,
            'buyer_address' => $customer->address,
            'status' => InvoiceStatus::Draft,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $item = $invoice->items()->create([
            'hs_code_id' => $hsCode?->id,
            'uom_id' => $uom?->id,
            'tax_rate_id' => $taxRate?->id,
            'sale_type_id' => $saleType?->id,
            'hs_code' => $fixture['hs_code'],
            'description' => $fixture['description'],
            'uom' => $fixture['uom'],
            'quantity' => $fixture['quantity'],
            'unit_price' => $fixture['unit_price'],
            'rate_percent' => $fixture['rate_percent'],
            'discount' => 0,
            'extra_tax' => 0,
            'further_tax' => 0,
            'fed_payable' => 0,
            'fixed_notified_value' => $fixture['fixed_notified_value'],
            'sale_type' => $fixture['sale_type'],
            'sro_schedule_number' => $fixture['sro_schedule_number'],
            'item_serial_number' => $fixture['item_serial_number'],
        ]);

        if ($fixture['payload_overrides'] !== []) {
            $item->updateQuietly($fixture['payload_overrides']);
        }

        $invoice->refresh()->load('items');
        $invoice->recalculateTotals();

        try {
            $validateResponse = $service->validateInvoice($invoice);
            $invoice->update([
                'status' => InvoiceStatus::Validated,
                'fbr_response_json' => $validateResponse,
                'error_message' => null,
            ]);

            $submitResponse = $service->submitInvoice($invoice);
            $finalizer->finalize($invoice, $submitResponse);
        } catch (Throwable $exception) {
            $message = $this->fbrFailureMessage($exception);

            $invoice->update([
                'status' => InvoiceStatus::Failed,
                'error_message' => $message,
            ]);

            return redirect()->route('admin.mock-fbr-console')
                ->with('error', "Demo invoice {$invoice->invoice_number} could not be submitted: {$message}");
        }

        return redirect()->route('admin.mock-fbr-console')->with('status', "Demo invoice {$invoice->invoice_number} validated and submitted to mock FBR.");
    }

    private function fbrFailureMessage(Throwable $exception): string
    {
        if ($exception instanceof RequestException && $exception->response) {
            $status = $exception->response->status();
            $body = $exception->response->json();
            $message = data_get($body, 'validationResponse.error')
                ?: data_get($body, 'fault.message')
                ?: data_get($body, 'message')
                ?: $exception->response->body();

            return trim("FBR returned HTTP {$status}. ".Str::limit((string) $message, 240));
        }

        return Str::limit($exception->getMessage(), 240);
    }
}
