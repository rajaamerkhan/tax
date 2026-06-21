<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css').'?v='.filemtime(public_path('assets/css/app.css')) }}" rel="stylesheet">
</head>
<body class="auth-screen">
<div class="auth-card">
    <div class="mb-4">
        <div class="eyebrow">Pakistan FBR / PRAL</div>
        <h1 class="h3 mb-2">{{ config('app.name') }}</h1>
        <p class="text-secondary mb-0">Login to manage draft, submitted, editable, and locked invoices.</p>
    </div>
    <form method="POST" action="{{ route('login.store') }}" class="row g-3">
        @csrf
        <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false">
        </div>
        <div class="col-12">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-primary">Sign In</button>
        </div>
    </form>
</div>
</body>
</html>
