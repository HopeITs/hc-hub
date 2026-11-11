<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\City;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Create or edit a governorate from HR settings. Cities are Core rows that
 * nothing else manages. The 3-letter slug names the regional HR role
 * (HR-ALP) and the code is printed on digital IDs, so both are unique and
 * only national HR may create a city or change them; a regional HR role
 * edits its own city's names and HR hotline.
 */
final class SaveCity
{
    /**
     * @param  array{name_en?: mixed, name_ar?: mixed, slug?: mixed, code?: mixed, hr_hotline?: mixed}  $input
     *
     * @throws ValidationException keyed by column name
     */
    public function handle(?int $cityId, array $input): City
    {
        Gate::authorize($cityId === null ? 'project-basics.create' : 'project-basics.update');

        $access = HrAccess::for(Auth::user());

        if ($cityId === null && ! $access->isNational()) {
            throw ValidationException::withMessages(['name_en' => __('hr_settings.city_not_authorized')]);
        }

        if ($cityId !== null) {
            $access->assertCity($cityId, 'name_en');
        }

        $clean = fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null;

        $data = [
            'name_en' => $clean($input['name_en'] ?? null),
            'name_ar' => $clean($input['name_ar'] ?? null),
            'slug' => ($slug = $clean($input['slug'] ?? null)) === null ? null : strtoupper($slug),
            'code' => $clean($input['code'] ?? null),
            'hr_hotline' => $clean($input['hr_hotline'] ?? null),
        ];

        $rules = [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'hr_hotline' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ()\-]{3,32}\z/'],
        ];

        // Only national HR touches the identifiers other records are named after.
        if ($access->isNational()) {
            $rules['slug'] = ['required', 'string', 'alpha', 'size:3', Rule::unique('cities', 'slug')->ignore($cityId)];
            $rules['code'] = ['required', 'string', 'regex:/^[0-9]{3}\z/', Rule::unique('cities', 'code')->ignore($cityId)];
        } else {
            unset($data['slug'], $data['code']);
        }

        $attributes = [];

        foreach (array_keys($rules) as $field) {
            $attributes[$field] = __('hr_settings.fields.'.$field);
        }

        $validated = Validator::make($data, $rules, [
            'hr_hotline.regex' => __('hr_settings.cities.hotline_invalid'),
            'code.regex' => __('hr_settings.cities.code_invalid'),
        ], $attributes)->validate();

        return DB::transaction(function () use ($cityId, $validated): City {
            $city = $cityId === null ? new City : City::query()->lockForUpdate()->findOrFail($cityId);
            $city->fill($validated)->save();

            return $city;
        });
    }
}
