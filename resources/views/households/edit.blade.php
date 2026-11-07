<x-app-layout title="Edit household">
    <x-slot name="header"><h1>Edit household {{ $household->code }}</h1></x-slot>

    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('households.update', $household) }}">
            @method('PUT')
            @include('households._form')
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-rg">Save changes</button>
                <a href="{{ route('households.show', $household) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</x-app-layout>
