@csrf
@if($managedUser->exists)
    @method('PUT')
@endif
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" value="{{ old('name', $managedUser->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="{{ old('email', $managedUser->email) }}" required autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" data-no-autofill data-lpignore="true" data-1p-ignore>
    </div>
    <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="{{ old('phone', $managedUser->phone) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Role</label>
        <select class="form-select" name="role" required>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $managedUser->role?->value ?? $managedUser->role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ $managedUser->exists ? 'New Password' : 'Password' }}</label>
        <input class="form-control" type="password" name="password" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore @required(! $managedUser->exists)>
    </div>
    <div class="col-md-4">
        <label class="form-label">Confirm Password</label>
        <input class="form-control" type="password" name="password_confirmation" autocomplete="new-password" data-no-autofill data-lpignore="true" data-1p-ignore @required(! $managedUser->exists)>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">{{ $managedUser->exists ? 'Save User' : 'Create User' }}</button>
    <a class="btn btn-outline-light" href="{{ route('users.index') }}">Cancel</a>
</div>
