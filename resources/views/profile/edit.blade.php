<x-app-layout title="Profile">
    <x-slot name="header">
        <h1>My profile</h1>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
