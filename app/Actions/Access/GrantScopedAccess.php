<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Models\AccessGrant;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;

/**
 * Give a user a role or one permission on one scope for a time (roadmap 4.4,
 * D5). Whoever manages permissions may grant; a grant carrying a right over
 * users, roles or permissions is an administrator's alone, as with roles
 * (UserPolicy). An end date must come after the start.
 */
class GrantScopedAccess
{
    /**
     * @param  array{role?: string|null, permission?: string|null, scope_type: string, scope_id: int, starts_at?: string|null, ends_at?: string|null, delegated_from_user_id?: int|null, reason?: string|null}  $input
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $input): AccessGrant
    {
        Gate::authorize('access-management.manage-permissions');

        $data = Validator::make($input, [
            'role' => ['nullable', 'required_without:permission', 'prohibits:permission', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permission' => ['nullable', 'required_without:role', 'string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
            'scope_type' => ['required', 'string', Rule::in(config('access-control.access_grants.scope_types', []))],
            'scope_id' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:'.($input['starts_at'] ?? 'now')],
            'delegated_from_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'reason' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $role = isset($data['role']) ? Role::findByName($data['role'], 'web') : null;
        $permission = isset($data['permission']) ? Permission::findByName($data['permission'], 'web') : null;

        $carried = $role !== null
            ? $role->permissions()->pluck('name')->all()
            : [(string) $permission?->name];
        $actor = Auth::user();

        if (collect($carried)->contains(fn (mixed $name): bool => str_starts_with((string) $name, 'access-management.'))
            && ! ($actor instanceof User && $actor->isAdministrator())) {
            throw ValidationException::withMessages([
                $role !== null ? 'role' : 'permission' => __('access.grants.administrators_only'),
            ]);
        }

        return AccessGrant::query()->create([
            'user_id' => $user->id,
            'role_id' => $role?->getKey(),
            'permission_id' => $permission?->getKey(),
            'scope_type' => $data['scope_type'],
            'scope_id' => (int) $data['scope_id'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'granted_by' => Auth::id(),
            'delegated_from_user_id' => $data['delegated_from_user_id'] ?? null,
            'reason' => $data['reason'] ?? null,
        ]);
    }
}
