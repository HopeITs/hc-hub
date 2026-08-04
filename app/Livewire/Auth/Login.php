<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public ?int $selectedRoleId = null;
    public array $roleOptions = [];

    /** NEW */
    public bool $employeeInactive = false;
    public string $inactiveMessage = '';

    public function render()
    {
        // $title / $description are read by partials/head.blade.php through the layout.
        return view('livewire.auth.login')
            ->title(__('auth.meta-title'))
            ->layoutData(['description' => __('auth.meta-description')]);
    }

    public function updatedEmail(): void
    {
        $this->reset([
            'selectedRoleId',
            'roleOptions',
            'employeeInactive',
            'inactiveMessage',
        ]);

        if ($this->email === '') {
            return;
        }

        $user = User::query()
            ->where('email', $this->email)
            ->with(['roles', 'profile'])
            ->first();

        // Unknown email or no employee → do nothing (no leaks)
        if (!$user || !$user->profile) {
            return;
        }

        // Employee exists but inactive → block immediately
        if (!$user->profile->is_active) {
            $this->employeeInactive = true;
            $this->inactiveMessage = __('You cannot access this website anymore');
            return;
        }

        // Normal role handling
        if ($user->roles()->count() > 1) {
            $this->roleOptions = $user->roles
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
                ->values()
                ->all();
        }
    }

    public function login(): void
    {
        $this->validate();

        // HARD BACKEND GUARD (cannot be bypassed)
        $user = User::query()
            ->where('email', $this->email)
            ->with('profile')
            ->first();

        if ($user && $user->profile && !$user->profile->is_active) {
            $this->employeeInactive = true;
            $this->inactiveMessage = __('You cannot access this website anymore');
            return;
        }

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        DB::transaction(function () use ($user) {
            $allIds = $user->roles()->pluck('roles.id')->all();

            if ($allIds) {
                $user->roles()->updateExistingPivot($allIds, ['is_active' => false]);
            }

            $roleIdToActivate = null;

            if ($this->selectedRoleId &&
                $user->roles()->where('roles.id', $this->selectedRoleId)->exists()) {
                $roleIdToActivate = $this->selectedRoleId;
            } else {
                $ids = $user->roles()->pluck('roles.id');
                $roleIdToActivate = $ids->count() === 1
                    ? $ids->first()
                    : $ids->sort()->first();
            }

            if ($roleIdToActivate) {
                $user->roles()->updateExistingPivot($roleIdToActivate, ['is_active' => true]);
                session(['active_role_id' => $roleIdToActivate]);
            }
        });

        $this->redirectIntended(
            default: route('dashboard', absolute: false),
            navigate: true
        );
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->email) . '|' . request()->ip()
        );
    }
}
