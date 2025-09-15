<?php

namespace App\Livewire\Auth;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class RoleSwitcher extends Component
{
    public ?string $activeRoleName = null;
    public $inactive = [];

    public function mount(): void
    {
        $this->refreshRoles();
    }

    #[On('refresh-role-switcher')]
    public function refreshRoles(): void
    {
        $user = auth()->user();

        $this->activeRoleName = $user?->activeRole()?->name ?? null;

        $this->inactive = $user
            ? $user->inactiveRoles()
                ->map(fn(Role $r) => ['id' => $r->id, 'name' => $r->name])
                ->values()
                ->all()
            : [];
    }

    public function switchTo(int $roleId): void
    {
        $user = auth()->user();

        abort_unless($user && $user->roles()->where('roles.id', $roleId)->exists(), 403);

        DB::transaction(function () use ($user, $roleId) {
            $ids = $user->roles()->pluck('roles.id')->all();
            if ($ids) {
                $user->roles()->updateExistingPivot($ids, ['isActive' => false]);
            }
            $user->roles()->updateExistingPivot($roleId, ['isActive' => true]);
        });

        session(['active_role_id' => $roleId]);

        $this->dispatch('notify', title: __('Role switched'), message: __('Role updated successfully.'));

        $this->refreshRoles();
    }

    public function render()
    {
        return view('livewire.auth.role-switcher');
    }
}
