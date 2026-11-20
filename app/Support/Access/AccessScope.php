<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\AccessGrant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\User;

/**
 * Whether a user holds a permission on one record's scope (roadmap 4.4, D5):
 * either they hold it everywhere (the usual can()), or an access grant gives
 * it to them on that scope right now. A policy passes the scopes that cover
 * the record, narrowest first, e.g. for a project:
 *
 *     ['project' => $project->id, 'program' => $project->program_id, 'city' => $project->city_id]
 *
 * so a grant on the program covers every project under it. A grant counts
 * from starts_at until ends_at and never once revoked. A granted role gives
 * the permissions that role holds; nothing else of the role applies.
 *
 * Off unless config('access-control.access_grants.enabled') is on: switched
 * off, only can() decides, exactly as before the table existed.
 */
final class AccessScope
{
    /** @var array<int, list<array{scope_type: string, scope_id: int, permissions: list<string>}>> */
    private array $active = [];

    /**
     * @param  array<string, int|null>  $scopes  scope type => id, e.g. ['project' => 7, 'program' => 2]
     */
    public function allows(User $user, string $permission, array $scopes): bool
    {
        return $user->can($permission) || $this->grants($user, $permission, $scopes);
    }

    /**
     * Whether an active grant alone gives the permission on one of the scopes.
     *
     * @param  array<string, int|null>  $scopes
     */
    public function grants(User $user, string $permission, array $scopes): bool
    {
        if (! self::enabled()) {
            return false;
        }

        foreach ($this->activeFor($user) as $grant) {
            if (($scopes[$grant['scope_type']] ?? null) === $grant['scope_id'] && in_array($permission, $grant['permissions'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The ids of one scope type the user holds the permission on through an
     * active grant (for lists: "which projects may I edit?").
     *
     * @return list<int>
     */
    public function scopeIds(User $user, string $permission, string $scopeType): array
    {
        if (! self::enabled()) {
            return [];
        }

        $ids = [];

        foreach ($this->activeFor($user) as $grant) {
            if ($grant['scope_type'] === $scopeType && in_array($permission, $grant['permissions'], true)) {
                $ids[] = $grant['scope_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    public function forget(): void
    {
        $this->active = [];
    }

    public static function enabled(): bool
    {
        return (bool) config('access-control.access_grants.enabled', false);
    }

    /**
     * @return list<array{scope_type: string, scope_id: int, permissions: list<string>}>
     */
    private function activeFor(User $user): array
    {
        $userId = (int) $user->getKey();

        if (isset($this->active[$userId])) {
            return $this->active[$userId];
        }

        $grants = AccessGrant::query()
            ->where('user_id', $userId)
            ->where(fn (Builder $query) => $query->activeAt())
            ->with(['permission:id,name', 'role.permissions:id,name'])
            ->get();

        $active = [];

        foreach ($grants as $grant) {
            $permissions = $grant->permission !== null
                ? [(string) $grant->permission->name]
                : ($grant->role?->permissions->pluck('name')->map(fn (mixed $name): string => (string) $name)->all() ?? []);

            $active[] = [
                'scope_type' => $grant->scope_type,
                'scope_id' => (int) $grant->scope_id,
                'permissions' => array_values($permissions),
            ];
        }

        return $this->active[$userId] = $active;
    }
}
