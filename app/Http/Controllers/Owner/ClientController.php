<?php

namespace App\Http\Controllers\Owner;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->withCount(['users', 'companyProfile'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($inner) use ($request): void {
                    $inner->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('email', 'like', '%'.$request->q.'%')
                        ->orWhere('contact_name', 'like', '%'.$request->q.'%');
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('owner.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('owner.clients.create', [
            'client' => new Client(['status' => 'active']),
            'admin' => new User(),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = DB::transaction(function () use ($request): Client {
            $client = Client::create($request->safe()->only(['name', 'contact_name', 'email', 'phone', 'status']));

            $client->users()->create([
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'phone' => $request->validated('admin_phone'),
                'role' => UserRole::Admin,
                'password' => $request->validated('admin_password'),
            ]);

            return $client;
        });

        return redirect()->route('owner.clients.edit', $client)->with('status', 'Client created successfully.');
    }

    public function edit(Client $client): View
    {
        return view('owner.clients.edit', [
            'client' => $client,
            'admin' => $client->users()->where('role', UserRole::Admin->value)->oldest()->first() ?? new User(),
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        DB::transaction(function () use ($request, $client): void {
            $client->update($request->safe()->only(['name', 'contact_name', 'email', 'phone', 'status']));

            $admin = $client->users()->where('role', UserRole::Admin->value)->oldest()->first();
            $adminData = [
                'client_id' => $client->id,
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'phone' => $request->validated('admin_phone'),
                'role' => UserRole::Admin,
            ];

            if ($request->filled('admin_password')) {
                $adminData['password'] = $request->validated('admin_password');
            }

            if ($admin) {
                $admin->update($adminData);
            } else {
                $client->users()->create(array_merge($adminData, [
                    'password' => $request->validated('admin_password'),
                ]));
            }
        });

        return back()->with('status', 'Client updated successfully.');
    }

    public function manage(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->status !== 'active', 422, 'Inactive clients cannot be managed.');

        $request->session()->put('managed_client_id', $client->id);
        $request->session()->put('managed_client_name', $client->name);

        return redirect()->route('dashboard')->with('status', 'Managing '.$client->name.'.');
    }

    public function stopManaging(Request $request): RedirectResponse
    {
        $request->session()->forget(['managed_client_id', 'managed_client_name']);

        return redirect()->route('owner.clients.index')->with('status', 'Returned to application owner mode.');
    }
}
