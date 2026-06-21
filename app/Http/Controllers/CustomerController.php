<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Province;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->forClient($this->tenantContext->clientId($request->user()))
            ->with('province')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($inner) use ($request): void {
                    $inner->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('ntn_cnic', 'like', '%'.$request->q.'%')
                        ->orWhere('strn', 'like', '%'.$request->q.'%');
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create', ['customer' => new Customer(), 'provinces' => $this->provinceOptions()]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create(array_merge($request->validated(), [
            'client_id' => $this->tenantContext->clientId($request->user()),
        ]));

        return redirect()->route('customers.show', $customer)->with('status', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $this->tenantContext->authorizeModel($customer);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->tenantContext->authorizeModel($customer);

        return view('customers.edit', ['customer' => $customer, 'provinces' => $this->provinceOptions()]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->tenantContext->authorizeModel($customer);
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->tenantContext->authorizeModel($customer);
        $customer->update(['status' => 'inactive']);
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deactivated successfully.');
    }

    private function provinceOptions(): Collection
    {
        $provinceLookup = Province::query()
            ->whereIn('name', [
                'Sindh',
                'Punjab',
                'Khyber Pakhtunkhwa',
                'Balochistan',
                'Islamabad Capital Territory',
                'Gilgit-Baltistan',
                'Azad Jammu and Kashmir',
            ])
            ->get()
            ->keyBy('name');

        return collect([
            ['lookup' => 'Sindh', 'label' => 'Sindh'],
            ['lookup' => 'Punjab', 'label' => 'Punjab'],
            ['lookup' => 'Khyber Pakhtunkhwa', 'label' => 'Khyber Pakhtunkhwa'],
            ['lookup' => 'Balochistan', 'label' => 'Balochistan'],
            ['lookup' => 'Islamabad Capital Territory', 'label' => 'Islamabad Capital Territory'],
            ['lookup' => 'Gilgit-Baltistan', 'label' => 'Gilgit-Baltistan'],
            ['lookup' => 'Azad Jammu and Kashmir', 'label' => 'Azad Jammu & Kashmir'],
        ])->map(function (array $province) use ($provinceLookup): ?Province {
            $model = $provinceLookup->get($province['lookup']);

            if (! $model) {
                return null;
            }

            $model->setAttribute('display_name', $province['label']);

            return $model;
        })->filter()->values();
    }
}
