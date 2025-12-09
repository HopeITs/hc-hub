<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

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
        if ($this->relationLoaded('roles')) {
            return $this->roles->firstWhere(fn($r) => (bool) ($r->pivot->is_active ?? false));
        }

        return $this->roles()->wherePivot('is_active', true)->first();
    }

    public function myRole(): ?string
    {
        return $this->activeRole()?->name ?? $this->roles()->first()->name;
    }

    public function activeRoleSlug(): ?string
    {
        $name = $this->myRole();
        return $name ? Str::slug($name) : null;
    }

    public function activeRoleIs(string ...$roles): bool
    {
        $current = $this->activeRoleSlug();
        if (!$current) {
            return false;
        }

        foreach ($roles as $r) {
            if ($current === Str::slug($r)) {
                return true;
            }
        }
        return false;
    }

    public function inactiveRoles()
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->filter(fn($r) => empty($r->pivot->is_active));
        }
        return $this->roles()->wherePivot('is_active', false)->get();
    }

    public function hasMultipleRoles(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->count() > 1;
        }
        return $this->roles()->limit(2)->count() > 1;
    }

    public function setActiveRole(int $roleId): void
    {
        DB::transaction(function () use ($roleId) {
            if (!$this->roles()->where('roles.id', $roleId)->exists()) {
                abort(403, 'Role does not belong to this user.');
            }

            $ids = $this->roles()->pluck('roles.id')->all();
            if (!empty($ids)) {
                $this->roles()->updateExistingPivot($ids, ['is_active' => false]);
            }
            $this->roles()->updateExistingPivot($roleId, ['is_active' => true]);

            if ($this->relationLoaded('roles')) {
                $this->roles->each(function ($r) use ($roleId) {
                    $r->pivot->is_active = (int) ($r->id === $roleId);
                });
            }
        });
    }



    public function getActiveRoleNameAttribute(): ?string
    {
        return $this->myRole();
    }

    public function initials(): string
    {
        $display = $this->profile?->name ?: '';
        return Str::of($display)->trim()->explode(' ')->take(2)->map(fn($w) => Str::substr($w, 0, 1))->implode('');
    }
}
