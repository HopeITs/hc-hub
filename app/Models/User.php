<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function getNameAttribute(): ?string
    {
        return $this->profile?->name_en;
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)->map(fn($word) => Str::substr($word, 0, 1))->implode('');
    }
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function myRole()
    {
        return $this->roles()->first()?->name;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'user_id');
    }

    public function accessibleFolders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class);
    }
}
