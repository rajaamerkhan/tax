<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ClientUserRequest;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientUserController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $clientId = $this->tenantContext->clientId($request->user());

        $users = User::query()
            ->where('client_id', $clientId)
            ->whereIn('role', [UserRole::Admin->value, UserRole::Accountant->value, UserRole::Viewer->value])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($inner) use ($request): void {
                    $inner->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('email', 'like', '%'.$request->q.'%')
                        ->orWhere('phone', 'like', '%'.$request->q.'%');
                });
            })
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'accountant' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', [
            'managedUser' => new User(['role' => UserRole::Viewer]),
            'roles' => $this->roles(),
        ]);
    }

    public function store(ClientUserRequest $request): RedirectResponse
    {
        User::create([
            'client_id' => $request->clientId(),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'role' => $request->validated('role'),
            'password' => $request->validated('password'),
        ]);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorizeClientUser($user);

        return view('users.edit', [
            'managedUser' => $user,
            'roles' => $this->roles(),
        ]);
    }

    public function update(ClientUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeClientUser($user);
        $this->preventRemovingLastAdmin($user, $request->validated('role'));

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'role' => $request->validated('role'),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return redirect()->route('users.edit', $user)->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeClientUser($user);

        abort_if($user->is(auth()->user()), 422, 'You cannot delete your own account.');
        abort_if($this->isLastAdmin($user), 422, 'At least one client admin must remain.');

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User deleted successfully.');
    }

    private function authorizeClientUser(User $user): void
    {
        $clientId = $this->tenantContext->clientId(auth()->user());

        abort_unless(
            (int) $user->client_id === (int) $clientId
            && in_array($user->role, [UserRole::Admin, UserRole::Accountant, UserRole::Viewer], true),
            404,
        );
    }

    private function preventRemovingLastAdmin(User $user, string $newRole): void
    {
        if ($user->role !== UserRole::Admin || $newRole === UserRole::Admin->value) {
            return;
        }

        abort_if($this->isLastAdmin($user), 422, 'At least one client admin must remain.');
    }

    private function isLastAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin
            && User::query()
                ->where('client_id', $user->client_id)
                ->where('role', UserRole::Admin->value)
                ->whereKeyNot($user->id)
                ->doesntExist();
    }

    private function roles(): array
    {
        return [
            UserRole::Admin->value => UserRole::Admin->label(),
            UserRole::Accountant->value => UserRole::Accountant->label(),
            UserRole::Viewer->value => UserRole::Viewer->label(),
        ];
    }
}
