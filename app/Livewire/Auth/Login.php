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

    public function render()
    {
        if ($this->email !== '' && empty($this->roleOptions)) {
            $user = User::where('email', $this->email)->with('roles')->first();
            if ($user && $user->roles()->count() > 1) {
                $this->roleOptions = $user->roles
                    ->map(fn($r) => ['id' => $r->id, 'name' => $r->name])
                    ->values()
                    ->all();
            }
        }

        return view('livewire.auth.login');
    }

    public function updatedEmail(): void
    {
        $this->selectedRoleId = null;
        $this->roleOptions = [];

        $user = User::query()
            ->where('email', $this->email)
            ->with('roles')
            ->first();

        if ($user && $user->roles()->count() > 1) {
            $this->roleOptions = $user->roles
                ->map(fn($r) => ['id' => $r->id, 'name' => $r->name])
                ->values()
                ->all();
        }
    }

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
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
                $user->roles()->updateExistingPivot($allIds, ['isActive' => false]);
            }

            $roleIdToActivate = null;

            if ($this->selectedRoleId && $user->roles()->where('roles.id', $this->selectedRoleId)->exists()) {
                $roleIdToActivate = $this->selectedRoleId;
            } else {
                $ids = $user->roles()->pluck('roles.id');
                if ($ids->count() === 1) {
                    $roleIdToActivate = $ids->first();
                } else {
                    $roleIdToActivate = $user->roles()->orderBy('roles.id')->pluck('roles.id')->first();
                }
            }

            if ($roleIdToActivate) {
                $user->roles()->updateExistingPivot($roleIdToActivate, ['isActive' => true]);
                session(['active_role_id' => $roleIdToActivate]);
            }
        });

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
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
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}
