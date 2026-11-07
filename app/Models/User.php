<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------
    // Role helpers
    // ---------------------------------------------------------------

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof Role ? $role->value : $role;
            if ($this->role?->value === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isCoordinator(): bool
    {
        return $this->hasRole(Role::Coordinator);
    }

    /** Admin or coordinator: the two "management" roles. */
    public function isManager(): bool
    {
        return $this->hasRole(Role::Admin, Role::Coordinator);
    }

    public function isFieldOfficer(): bool
    {
        return $this->hasRole(Role::FieldOfficer);
    }

    public function isWarehouseOfficer(): bool
    {
        return $this->hasRole(Role::WarehouseOfficer);
    }

    public function isShelterAssessor(): bool
    {
        return $this->hasRole(Role::ShelterAssessor);
    }

    public function roleLabel(): string
    {
        return $this->role?->label() ?? 'Unknown';
    }
}
