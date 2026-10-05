<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * One place to express "this user may only see and touch their own city".
 *
 * Several components previously re-derived the city by string-splitting the
 * role name — `explode('-', $user->myRole())[1]` — and each copy drifted. Some
 * applied the restriction to the dropdown but not to the validation behind it;
 * some applied it to the edit action but not to the delete; the table filter
 * and the export used different rules again. Divergence between a filter and
 * its matching check is what produced the earlier "invalid position in Syria"
 * bug, and the same pattern was still present here.
 *
 * Both halves must come from this trait: scopeToAuthorizedCity() for anything
 * that reads, assertCityIsAuthorized() for anything that writes.
 */
trait ScopesToAuthorizedCity
{
    /**
     * The cities the active role is confined to, or null when the role sees
     * all cities (super-admin, general-manager, HR-SYR headquarters).
     *
     * @return array<int, int>|null
     */
    protected function authorizedCityIds(): ?array
    {
        return auth()->user()?->activeCityIds();
    }

    /**
     * Restrict a query to the authorized city.
     *
     * @param  string  $column  the city foreign key on the queried table
     */
    protected function scopeToAuthorizedCity(Builder $query, string $column = 'city_id'): Builder
    {
        $cityIds = $this->authorizedCityIds();

        return $cityIds === null
            ? $query
            : $query->whereIn($query->qualifyColumn($column), $cityIds);
    }

    /**
     * Reject a submitted city id that the active role may not write to.
     *
     * Raised as a validation error rather than a 403 because it is reachable
     * by ordinary means (a stale dropdown after a role switch) as well as by
     * tampering.
     */
    protected function assertCityIsAuthorized(mixed $cityId, string $field = 'item.city_id'): int
    {
        $cityId = (int) $cityId;
        $authorized = $this->authorizedCityIds();

        if ($authorized !== null && ! in_array($cityId, $authorized, true)) {
            throw ValidationException::withMessages([
                $field => __('validation.city_not_authorized'),
            ]);
        }

        return $cityId;
    }
}
