<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\HsCode;
use App\Models\Province;
use App\Models\SaleType;
use App\Models\Scenario;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceAutocompleteController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request, string $resource): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        [$results, $more] = match ($resource) {
            'customers' => $this->customers($term, $page, $perPage),
            'provinces' => $this->provinces($term, $page, $perPage),
            'scenarios' => $this->scenarios($term, $page, $perPage),
            'hs-codes' => $this->hsCodes($term, $page, $perPage),
            'sale-types' => $this->saleTypes($term, $page, $perPage),
            default => [collect(), false],
        };

        abort_if(! in_array($resource, ['customers', 'provinces', 'scenarios', 'hs-codes', 'sale-types'], true), 404);

        return response()->json([
            'results' => $results->values()->all(),
            'pagination' => ['more' => $more],
        ]);
    }

    private function customers(string $term, int $page, int $perPage): array
    {
        $result = Customer::query()
            ->forClient($this->tenantContext->clientId(auth()->user()))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('ntn_cnic', 'like', '%'.$term.'%')
                        ->orWhere('strn', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'ntn_cnic', 'strn', 'address', 'buyer_type', 'province_id'], 'page', $page);

        return [
            $result->getCollection()->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'text' => $customer->name.($customer->ntn_cnic ? ' | '.$customer->ntn_cnic : ''),
                'name' => $customer->name,
                'ntn' => $customer->ntn_cnic,
                'strn' => $customer->strn,
                'address' => $customer->address,
                'buyer_type' => ucfirst($customer->buyer_type?->value ?? 'unregistered'),
                'province_id' => $customer->province_id,
            ]),
            $result->hasMorePages(),
        ];
    }

    private function provinces(string $term, int $page, int $perPage): array
    {
        $result = Province::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%')
                    ->orWhere('fbr_code', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'code'], 'page', $page);

        return [
            $result->getCollection()->map(fn (Province $province) => [
                'id' => $province->id,
                'text' => $province->name,
            ]),
            $result->hasMorePages(),
        ];
    }

    private function scenarios(string $term, int $page, int $perPage): array
    {
        $result = Scenario::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'code'], 'page', $page);

        return [
            $result->getCollection()->map(fn (Scenario $scenario) => [
                'id' => $scenario->id,
                'text' => $scenario->name,
            ]),
            $result->hasMorePages(),
        ];
    }

    private function hsCodes(string $term, int $page, int $perPage): array
    {
        $result = HsCode::query()
            ->with('uom:id,code,name')
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('code', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('code')
            ->paginate($perPage, ['id', 'code', 'description', 'uom_id'], 'page', $page);

        return [
            $result->getCollection()->map(fn (HsCode $hsCode) => [
                'id' => $hsCode->id,
                'text' => $hsCode->code.' - '.$hsCode->description,
                'code' => $hsCode->code,
                'description' => $hsCode->description,
                'uom_id' => $hsCode->uom_id,
            ]),
            $result->hasMorePages(),
        ];
    }

    private function saleTypes(string $term, int $page, int $perPage): array
    {
        $result = SaleType::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'code'], 'page', $page);

        return [
            $result->getCollection()->map(fn (SaleType $saleType) => [
                'id' => $saleType->id,
                'text' => $saleType->name,
                'name' => $saleType->name,
            ]),
            $result->hasMorePages(),
        ];
    }
}
