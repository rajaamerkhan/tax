<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = $user && in_array($user->role->value, $roles, true);

        if (! $allowed && $user?->isOwner() && ! in_array('owner', $roles, true)) {
            $allowed = app(TenantContext::class)->isManagingClient($user);
        }

        abort_unless($allowed, 403);

        return $next($request);
    }
}
