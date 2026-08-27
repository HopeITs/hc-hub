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
     * The city the active role is confined to, or null when the role sees all
     * cities (super-admin, general-manager, HR-SYR headquarters).
     */
    protected function authorizedCityId(): ?int
    {
        return auth()->user()?->activeCityId();
    }

    /**
     * Restrict a query to the authorized city.
     *
     * @param  string  $column  the city foreign key on the queried table
     */
    protected function scopeToAuthorizedCity(Builder $query, string $column = 'city_id'): Builder
    {
        $cityId = $this->authorizedCityId();

        return $cityId === null
            ? $query
            : $query->where($query->qualifyColumn($column), $cityId);
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
        $authorized = $this->authorizedCityId();

        if ($authorized !== null && $cityId !== $authorized) {
            throw ValidationException::withMessages([
                $field => __('validation.city_not_authorized'),
            ]);
        }

        return $cityId;
    }
}
