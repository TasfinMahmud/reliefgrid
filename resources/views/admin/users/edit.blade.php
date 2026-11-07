<x-app-layout title="Edit user">
    <x-slot name="header"><h1>Edit user: {{ $user->name }}</h1></x-slot>
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @method('PUT')
            @include('admin.users._form')
        </form>
    </div></div>
</x-app-layout>
