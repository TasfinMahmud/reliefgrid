<x-app-layout title="Users">
    <x-slot name="header">
        <h1>Users &amp; roles</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-rg btn-sm">New user</a>
    </x-slot>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">All roles</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }} @if($user->id === auth()->id())<span class="badge bg-info text-dark">you</span>@endif</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? '—' }}</td>
                        <td>{{ $user->roleLabel() }}</td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $user->is_active ? 'Active' : 'Deactivated' }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @can('toggleActive', $user)
                                <x-confirm-form :action="route('admin.users.toggle-active', $user)" method="PATCH" :confirm="$user->is_active ? 'Deactivate this user? They will be logged out.' : 'Reactivate this user?'">
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $user->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                                </x-confirm-form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
</x-app-layout>
