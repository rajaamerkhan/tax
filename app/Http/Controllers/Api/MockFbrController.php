<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Support\FbrSandboxProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MockFbrController extends Controller
{
    public function validateInvoiceSandbox(Request $request): JsonResponse
    {
        return $this->validateInvoice($request, true);
    }

    public function validateInvoiceProduction(Request $request): JsonResponse
    {
        return $this->validateInvoice($request, false);
    }

    public function postInvoiceSandbox(Request $request): JsonResponse
    {
        return $this->postInvoice($request, true);
    }

    public function postInvoiceProduction(Request $request): JsonResponse
    {
        return $this->postInvoice($request, false);
    }

    public function provinces(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['stateProvinceCode' => 7, 'stateProvinceDesc' => 'PUNJAB'],
            ['stateProvinceCode' => 8, 'stateProvinceDesc' => 'SINDH'],
            ['stateProvinceCode' => 9, 'stateProvinceDesc' => 'KHYBER PAKHTUNKHWA'],
            ['stateProvinceCode' => 10, 'stateProvinceDesc' => 'BALOCHISTAN'],
            ['stateProvinceCode' => 11, 'stateProvinceDesc' => 'ISLAMABAD'],
        ]);
    }

    public function documentTypes(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['docTypeId' => 4, 'docDescription' => 'Sale Invoice'],
            ['docTypeId' => 9, 'docDescription' => 'Debit Note'],
        ]);
    }

    public function itemCodes(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['hS_CODE' => '2523.2910', 'description' => 'PORTLAND CEMENT'],
            ['hS_CODE' => '8432.1010', 'description' => 'CHISEL PLOUGHS'],
        ]);
    }

    public function sroItemCodes(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['srO_ITEM_ID' => 724, 'srO_ITEM_DESC' => '9'],
            ['srO_ITEM_ID' => 728, 'srO_ITEM_DESC' => '1'],
        ]);
    }

    public function transactionTypes(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['transactioN_TYPE_ID' => 18, 'transactioN_DESC' => 'Goods at standard rate (default)'],
            ['transactioN_TYPE_ID' => 82, 'transactioN_DESC' => 'DTRE goods'],
        ]);
    }

    public function uoms(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['uoM_ID' => 13, 'description' => 'KG'],
            ['uoM_ID' => 77, 'description' => 'Square Metre'],
            ['uoM_ID' => 91, 'description' => 'Numbers, pieces, units'],
        ]);
    }

    public function sroSchedules(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['srO_ID' => 7, 'srO_DESC' => 'Zero Rated Gas'],
            ['srO_ID' => 8, 'srO_DESC' => '5th Schedule'],
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['ratE_ID' => 734, 'ratE_DESC' => '18%', 'ratE_VALUE' => 18],
            ['ratE_ID' => 280, 'ratE_DESC' => '0%', 'ratE_VALUE' => 0],
        ]);
    }

    public function hsUom(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['uoM_ID' => 13, 'description' => 'KG'],
        ]);
    }

    public function sroItems(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        return response()->json([
            ['srO_ITEM_ID' => 17853, 'srO_ITEM_DESC' => '50'],
            ['srO_ITEM_ID' => 17854, 'srO_ITEM_DESC' => '51'],
        ]);
    }

    public function statl(Request $request): JsonResponse
    {
        $this->authorizeToken($request);
        $regNo = $request->input('regno', '0788762');

        return response()->json([
            'status code' => Str::endsWith($regNo, '2') ? '00' : '01',
            'status' => Str::endsWith($regNo, '2') ? 'Active' : 'In-Active',
        ]);
    }

    public function registrationType(Request $request): JsonResponse
    {
        $this->authorizeToken($request);
        $registrationNo = $request->input('Registration_No', $request->input('registration_no', '0788762'));
        $registered = Str::endsWith($registrationNo, '2');

        return response()->json([
            'statuscode' => $registered ? '00' : '01',
            'REGISTRATION_NO' => $registrationNo,
            'REGISTRATION_TYPE' => $registered ? 'Registered' : 'unregistered',
        ]);
    }

    private function validateInvoice(Request $request, bool $sandbox): JsonResponse
    {
        $this->authorizeToken($request);

        $errors = $this->validatePayload($request->all(), $sandbox, false);

        if ($errors !== []) {
            return response()->json([
                'dated' => now()->format('Y-m-d H:i:s'),
                'validationResponse' => [
                    'statusCode' => '00',
                    'status' => 'Invalid',
                    'errorCode' => null,
                    'error' => '',
                    'invoiceStatuses' => [[
                        'itemSNo' => '1',
                        'statusCode' => '01',
                        'status' => 'Invalid',
                        'errorCode' => $errors[0]['errorCode'],
                        'error' => $errors[0]['error'],
                    ]],
                ],
            ]);
        }

        return response()->json([
            'dated' => now()->format('Y-m-d H:i:s'),
            'validationResponse' => [
                'statusCode' => '00',
                'status' => 'Valid',
                'errorCode' => null,
                'error' => '',
                'invoiceStatuses' => collect($request->input('items', []))->values()->map(fn ($item, $index) => [
                    'itemSNo' => (string) ($index + 1),
                    'statusCode' => '00',
                    'status' => 'Valid',
                    'errorCode' => null,
                    'error' => '',
                ])->all(),
            ],
        ]);
    }

    private function postInvoice(Request $request, bool $sandbox): JsonResponse
    {
        $this->authorizeToken($request);

        $errors = $this->validatePayload($request->all(), $sandbox, true);

        if ($errors !== []) {
            $first = $errors[0];

            return response()->json([
                'dated' => now()->format('Y-m-d H:i:s'),
                'validationResponse' => [
                    'statusCode' => $first['topLevel'] ? '01' : '00',
                    'status' => 'Invalid',
                    'errorCode' => $first['topLevel'] ? $first['errorCode'] : null,
                    'error' => $first['topLevel'] ? $first['error'] : '',
                    'invoiceStatuses' => $first['topLevel'] ? null : [[
                        'itemSNo' => '1',
                        'statusCode' => '01',
                        'status' => 'Invalid',
                        'invoiceNo' => null,
                        'errorCode' => $first['errorCode'],
                        'error' => $first['error'],
                    ]],
                ],
            ]);
        }

        $invoiceNumber = $this->issueInvoiceNumber((string) $request->input('sellerNTNCNIC', '7000007'));

        return response()->json([
            'invoiceNumber' => $invoiceNumber,
            'dated' => now()->format('Y-m-d H:i:s'),
            'validationResponse' => [
                'statusCode' => '00',
                'status' => 'Valid',
                'error' => '',
                'invoiceStatuses' => collect($request->input('items', []))->values()->map(fn ($item, $index) => [
                    'itemSNo' => (string) ($index + 1),
                    'statusCode' => '00',
                    'status' => 'Valid',
                    'invoiceNo' => $invoiceNumber.'-'.($index + 1),
                    'errorCode' => '',
                    'error' => '',
                ])->all(),
            ],
        ]);
    }

    private function validatePayload(array $payload, bool $sandbox, bool $isPost): array
    {
        $required = [
            'invoiceType', 'invoiceDate', 'sellerNTNCNIC', 'sellerBusinessName', 'sellerProvince', 'sellerAddress',
            'buyerBusinessName', 'buyerProvince', 'buyerAddress', 'buyerRegistrationType', 'invoiceRefNo', 'items',
        ];

        if ($sandbox) {
            $required[] = 'scenarioId';
        }

        foreach ($required as $field) {
            if (! Arr::has($payload, $field) || Arr::get($payload, $field) === '') {
                return [[
                    'errorCode' => '0001',
                    'error' => "Missing required field: {$field}.",
                    'topLevel' => true,
                ]];
            }
        }

        $allowedScenarios = $this->mockAllowlist('allowed_scenarios');
        $scenarioId = (string) Arr::get($payload, 'scenarioId', '');

        if ($sandbox && $allowedScenarios !== [] && ! in_array($scenarioId, $allowedScenarios, true)) {
            return [[
                'errorCode' => 'MOCK_SCENARIO_NOT_ALLOWED',
                'error' => "Scenario {$scenarioId} is not enabled for this sandbox mock profile.",
                'topLevel' => true,
            ]];
        }

        $items = Arr::wrap($payload['items'] ?? []);
        if ($items === []) {
            return [[
                'errorCode' => '0002',
                'error' => 'At least one invoice item is required.',
                'topLevel' => true,
            ]];
        }

        $allowedSaleTypes = $this->mockAllowlist('allowed_sale_types');

        $itemRequired = ['hsCode', 'productDescription', 'rate', 'uoM', 'quantity', 'totalValues', 'valueSalesExcludingST', 'fixedNotifiedValueOrRetailPrice', 'salesTaxApplicable', 'salesTaxWithheldAtSource', 'saleType'];

        foreach ($items as $index => $item) {
            foreach ($itemRequired as $field) {
                if (! array_key_exists($field, $item) || $item[$field] === '' || $item[$field] === null) {
                    return [[
                        'errorCode' => $field === 'rate' ? '0046' : '0052',
                        'error' => $field === 'rate' ? 'Provide rate.' : "Provide {$field}.",
                        'topLevel' => false,
                    ]];
                }
            }

            $saleType = (string) $item['saleType'];

            if ($sandbox && $allowedSaleTypes !== [] && ! in_array($saleType, $allowedSaleTypes, true)) {
                return [[
                    'errorCode' => 'MOCK_SALE_TYPE_NOT_ALLOWED',
                    'error' => "Sale type {$saleType} is not enabled for this sandbox mock profile.",
                    'topLevel' => false,
                ]];
            }

            if (! preg_match('/^\d{4}\.\d{4}$/', (string) $item['hsCode'])) {
                return [[
                    'errorCode' => '0052',
                    'error' => 'Provide proper HS Code with invoice no. null',
                    'topLevel' => $isPost,
                ]];
            }
        }

        return [];
    }

    private function mockAllowlist(string $key): array
    {
        $businessNature = CompanyProfile::query()->value('fbr_business_nature');
        $profileValues = match ($key) {
            'allowed_scenarios' => FbrSandboxProfile::allowedScenariosForBusinessNature($businessNature),
            'allowed_sale_types' => FbrSandboxProfile::allowedSaleTypesForBusinessNature($businessNature),
            default => [],
        };

        if ($profileValues !== []) {
            return $profileValues;
        }

        return array_values(array_filter(array_map(
            fn (mixed $value): string => trim((string) $value),
            Arr::wrap(config("fbr.mock.{$key}", [])),
        )));
    }

    private function authorizeToken(Request $request): void
    {
        $configured = config('fbr.security_token');
        $provided = Str::replaceFirst('Bearer ', '', (string) $request->header('Authorization'));

        abort_unless($configured && hash_equals((string) $configured, $provided), 401, 'Unauthorized');
    }

    private function issueInvoiceNumber(string $sellerNtnCnic): string
    {
        return preg_replace('/\D+/', '', $sellerNtnCnic).'DI'.now()->format('YmdHisv');
    }
}
