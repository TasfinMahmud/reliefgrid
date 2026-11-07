<section>
    <h2 class="h5 text-danger">Delete account</h2>
    <p class="small text-muted">Once your account is deleted, you will no longer be able to log in. Records you created are kept for audit purposes.</p>

    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">Delete account</button>

    <x-modal-form id="confirmUserDeletion" title="Delete your account?" :action="route('profile.destroy')" method="DELETE" submit="Delete account" submit-class="btn-danger">
        <p class="small text-muted">Enter your password to confirm you would like to permanently delete your account.</p>
        <div class="mb-2">
            <label for="delete_password" class="form-label">Password</label>
            <input id="delete_password" name="password" type="password" class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif" placeholder="Password">
            @if($errors->userDeletion->has('password'))
                <div class="invalid-feedback d-block">{{ $errors->userDeletion->first('password') }}</div>
            @endif
        </div>
    </x-modal-form>
</section>

@if($errors->userDeletion->isNotEmpty())
    <script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal('#confirmUserDeletion').show());</script>
@endif
