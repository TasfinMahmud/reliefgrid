<section>
    <h2 class="h5">Update password</h2>
    <p class="small text-muted">Use a long, random password to keep your account secure.</p>

    @if (session('status') === 'password-updated')
        <div class="alert alert-success py-2">Password updated.</div>
    @endif

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="update_password_current_password" class="form-label">Current password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password">
            @if($errors->updatePassword->has('current_password'))
                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('current_password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label">New password</label>
            <input id="update_password_password" name="password" type="password" class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif" autocomplete="new-password">
            @if($errors->updatePassword->has('password'))
                <div class="invalid-feedback d-block">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-rg">Update password</button>
    </form>
</section>
