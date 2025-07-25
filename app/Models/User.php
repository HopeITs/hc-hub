<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];
    public array $translatable = ['name'];

    public function getTranslatedNameAttribute()
    {
        return $this->name[app()->getLocale()] ?? ($this->name['en'] ?? null);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

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
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)->map(fn($word) => Str::substr($word, 0, 1))->implode('');
    }

    public function myRole()
    {
        return 'مطور مواقع انترنت';
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    // public function hasPermission($permission)
    // {
    //     return $this->roles()
    //         ->whereHas('permissions', function ($query) use ($permission) {
    //             $query->where('name', $permission);
    //         })
    //         ->exists();
    // }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }
}
