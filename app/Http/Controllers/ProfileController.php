<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function edit(): View
    {
        return view('profile.edit', [
            'profileUser' => $this->profileUser(),
            'isManagedClientProfile' => $this->tenantContext->isManagingClient(auth()->user()),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->profileUser()->update($request->validated());

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $this->profileUser()->update(['password' => $request->validated()['password']]);

        return back()->with('status', 'Password changed successfully.');
    }

    private function profileUser()
    {
        $profileUser = $this->tenantContext->clientUser(auth()->user());

        abort_unless($profileUser, 404);

        return $profileUser;
    }
}
