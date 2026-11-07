<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Administrator user management: create staff accounts, assign roles,
 * activate/deactivate accounts.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.strtolower($request->string('q')).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(email) LIKE ?', [$like]));
            })
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::options()]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => Role::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;
        $user = User::create($data);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created as {$user->roleLabel()}.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['user' => $user, 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== Role::Admin->value) {
            return back()->withErrors(['role' => 'You cannot remove your own administrator role.'])->withInput();
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active ? "{$user->name} reactivated." : "{$user->name} deactivated.");
    }
}
