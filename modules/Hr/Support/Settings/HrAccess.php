<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Settings;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\City;
use Modules\Core\Models\User;

/**
 * Who a user is to HR: national HR (sees every governorate), a regional HR
 * role (HR-ALP sees only Aleppo) or neither. The old HR app read this from
 * the active role's name; here the active role is users.active_role_id, and
 * when that role is not an HR one the strongest HR role the user holds
 * counts, since hub permissions add up across roles.
 *
 * Reads use scopeToCities(), writes use assertCity(): both halves of "only
 * my governorate" come from the same list.
 */
final class HrAccess
{
    /** HR's own roles, strongest first. */
    private const array HR_ROLES = ['super-admin', 'HR-SYR', 'general-manager', 'manager', 'employee'];

    /** @var array<int, self> */
    private static array $resolved = [];

    private function __construct(
        private readonly User $user,
        private readonly ?string $role,
    ) {}

    public static function for(User $user): self
    {
        return self::$resolved[$user->id] ??= new self($user, self::resolveRole($user));
    }

    /** Forget what was resolved (after a role change in the same request). */
    public static function flush(): void
    {
        self::$resolved = [];
    }

    /** The HR role that decides this user's scope, or null when they hold none. */
    public function role(): ?string
    {
        return $this->role;
    }

    /** National HR and administrators: every governorate, every record. */
    public function isNational(): bool
    {
        return $this->user->isAdministrator() || in_array($this->role, ['super-admin', 'HR-SYR'], true);
    }

    /** A regional HR role (HR-ALP, HR-HMS...). */
    public function isRegionalHr(): bool
    {
        return ! $this->isNational() && $this->role !== null && str_starts_with($this->role, 'HR-');
    }

    public function isHr(): bool
    {
        return $this->isNational() || $this->isRegionalHr();
    }

    public function isGeneralManager(): bool
    {
        return $this->role === 'general-manager';
    }

    /**
     * The governorates a regional HR role is confined to; null when the user
     * is not confined (national HR, or not HR at all: other rules apply).
     *
     * @return list<int>|null
     */
    public function cityIds(): ?array
    {
        if (! $this->isRegionalHr()) {
            return null;
        }

        $bySlug = City::idsBySlug();
        $ids = [];

        foreach (City::slugsForHrRole((string) $this->role) as $slug) {
            $id = $bySlug[strtoupper($slug)] ?? null;

            if ($id !== null && $id !== 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeToCities(Builder $query, string $column = 'city_id'): Builder
    {
        $cityIds = $this->cityIds();

        return $cityIds === null ? $query : $query->whereIn($query->qualifyColumn($column), $cityIds);
    }

    /**
     * Refuse a governorate the user may not write to (a stale picker after a
     * role switch reaches this as easily as tampering, hence a field error).
     *
     * @throws ValidationException
     */
    public function assertCity(mixed $cityId, string $field = 'cityId'): int
    {
        $cityId = (int) $cityId;
        $allowed = $this->cityIds();

        if ($allowed !== null && ! in_array($cityId, $allowed, true)) {
            throw ValidationException::withMessages([$field => __('hr_settings.city_not_authorized')]);
        }

        return $cityId;
    }

    private static function resolveRole(User $user): ?string
    {
        $active = $user->active_role_id === null
            ? null
            : Role::query()->whereKey($user->active_role_id)->value('name');

        if (is_string($active) && self::isHrRole($active)) {
            return $active;
        }

        $held = $user->getRoleNames()->map(fn (mixed $name): string => (string) $name)->filter(self::isHrRole(...));

        foreach (['super-admin', 'HR-SYR'] as $name) {
            if ($held->contains($name)) {
                return $name;
            }
        }

        $regional = $held->first(fn (string $name): bool => str_starts_with($name, 'HR-'));

        if ($regional !== null) {
            return $regional;
        }

        foreach (['general-manager', 'manager', 'employee'] as $name) {
            if ($held->contains($name)) {
                return $name;
            }
        }

        return null;
    }

    private static function isHrRole(string $name): bool
    {
        return in_array($name, self::HR_ROLES, true) || str_starts_with($name, 'HR-');
    }
}
