<x-app-layout title="Register household">
    <x-slot name="header"><h1>Register household</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('households.store') }}">
            @include('households._form', ['household' => new \App\Models\Household])

            <div class="form-check mt-3">
                <input type="hidden" name="issue_token" value="0">
                <input class="form-check-input" type="checkbox" id="issue_token" name="issue_token" value="1" checked>
                <label class="form-check-label" for="issue_token">Issue a QR token card immediately after registration</label>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">Register household</button>
                <a href="{{ route('households.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
