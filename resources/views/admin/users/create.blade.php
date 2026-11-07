<x-app-layout title="New user">
    <x-slot name="header"><h1>New user</h1></x-slot>
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @include('admin.users._form', ['user' => new \App\Models\User])
        </form>
    </div></div>
</x-app-layout>
