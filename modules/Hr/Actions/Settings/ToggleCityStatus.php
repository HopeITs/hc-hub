<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\City;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Turn a governorate on or off (project-basics.update, as in the old app).
 * An inactive city leaves the pickers; nothing that already points at it is
 * touched. The nationwide pseudo-governorate (SYR) always stays on.
 */
final class ToggleCityStatus
{
    /**
     * @throws ValidationException
     */
    public function handle(int $cityId): City
    {
        Gate::authorize('project-basics.update');

        HrAccess::for(Auth::user())->assertCity($cityId, 'city');

        return DB::transaction(function () use ($cityId): City {
            $city = City::query()->lockForUpdate()->findOrFail($cityId);

            if ($city->is_active && strtoupper(trim($city->slug)) === City::NATIONAL_SLUG) {
                throw ValidationException::withMessages(['city' => __('hr_settings.cities.national_stays_active')]);
            }

            $city->is_active = ! $city->is_active;
            $city->save();

            return $city;
        });
    }
}
