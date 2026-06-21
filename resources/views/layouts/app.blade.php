<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css').'?v='.filemtime(public_path('assets/css/app.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div>
            <div class="brand">{{ config('app.name') }}</div>
            @php($tenantContext = app(\App\Support\TenantContext::class))
            @php($isManagingClient = $tenantContext->isManagingClient(auth()->user()))
            @php($managedClient = $tenantContext->client(auth()->user()))
            @unless(auth()->user()?->isOwner() && ! $isManagingClient)
                @php($currentFbrEnvironment = app(\App\Support\FbrEnvironmentContext::class)->current())
                <div class="env-badge env-{{ $currentFbrEnvironment }}">{{ strtoupper($currentFbrEnvironment) }}</div>
            @endunless
            @if($isManagingClient && $managedClient)
                <div class="small text-secondary mt-2">Managing {{ $managedClient->name }}</div>
            @endif
            <nav class="nav flex-column menu mt-4">
                @if(auth()->user()?->canManageClients() && ! $isManagingClient)
                    <a class="nav-link {{ request()->routeIs('owner.clients.*') ? 'active' : '' }}" href="{{ route('owner.clients.index') }}"><i class="bi bi-building-add"></i> Clients</a>
                @endif
                @if(! auth()->user()?->isOwner() || $isManagingClient)
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><i class="bi bi-receipt-cutoff"></i> Invoices</a>
                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-people"></i> Customers</a>
                    @if(auth()->user()?->canEditInvoices())
                        <a class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}"><i class="bi bi-upload"></i> Import</a>
                    @endif
                @endif
                @if(auth()->user()?->canManageSettings())
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-person-gear"></i> Users</a>
                    <a class="nav-link {{ request()->routeIs('company.*') ? 'active' : '' }}" href="{{ route('company.edit') }}"><i class="bi bi-building"></i> Company</a>
                    <a class="nav-link {{ request()->routeIs('reference-data.*') ? 'active' : '' }}" href="{{ route('reference-data.index') }}"><i class="bi bi-diagram-3"></i> Reference Data</a>
                    <a class="nav-link {{ request()->routeIs('admin.mock-fbr-console') ? 'active' : '' }}" href="{{ route('admin.mock-fbr-console') }}"><i class="bi bi-terminal"></i> Mock FBR Console</a>
                @endif
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle"></i> Profile</a>
            </nav>
        </div>
        <div class="small text-secondary">
            @if($isManagingClient)
                <form method="POST" action="{{ route('owner.clients.stop-managing') }}" class="mb-3">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-light w-100">Exit Client</button>
                </form>
            @endif
            @php($sidebarUser = $isManagingClient ? $tenantContext->clientUser(auth()->user()) : auth()->user())
            <div>{{ $sidebarUser?->name }}</div>
            <div>{{ $sidebarUser?->role->label() }}</div>
            <div class="sidebar-version">{{ config('app.name') }} v{{ config('app.version') }}</div>
        </div>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <div>
                <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                <div class="text-secondary small">Realtime invoicing workflow for Pakistan FBR / PRAL</div>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if((! auth()->user()?->isOwner() || $isManagingClient) && auth()->user()?->canEditInvoices())
                    <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Invoice</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light">Logout</button>
                </form>
            </div>
        </header>

        @if(session('status'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@stack('scripts')
<script src="{{ asset('assets/js/app.js').'?v='.filemtime(public_path('assets/js/app.js')) }}"></script>
</body>
</html>
