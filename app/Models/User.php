<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['email', 'password', 'employee_id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'user_id');
    }

    public function accessibleFolders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class);
    }

    public function activeRole(): ?Role
    {
        return $this->roles()
            ->wherePivot('is_active', true)
            ->first();
    }

    public function myRole(): ?string
    {
        return $this->activeRole()?->name;
    }

    public function inactiveRoles()
    {
        return $this->roles()
            ->wherePivot('is_active', false)
            ->get();
    }

    public function hasMultipleRoles(): bool
    {
        return $this->roles()->limit(2)->count() > 1;
    }

    public function setActiveRole(int $roleId): void
    {
        DB::transaction(function () use ($roleId) {
            if (!$this->roles()->where('roles.id', $roleId)->exists()) {
                abort(403, 'Role does not belong to this user.');
            }

            $ids = $this->roles()->pluck('roles.id')->all();
            if ($ids) {
                $this->roles()->updateExistingPivot($ids, ['is_active' => false]);
            }

            $this->roles()->updateExistingPivot($roleId, ['is_active' => true]);
        });
    }

    public function hasRole(string $roleName): bool
    {
        return $this->relationLoaded('roles')
            ? $this->roles->contains('name', $roleName)
            : $this->roles()->where('name', $roleName)->exists();
    }

    public function getActiveRoleNameAttribute(): ?string
    {
        return $this->activeRole()?->name;
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')
            ->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('');
    }
}
