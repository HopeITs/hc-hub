<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Positions;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Create or edit a position (the old PositionForm). A regional HR role keeps
 * to its cities; a center must sit in the position's city (any center for a
 * national position); managers may be in another city (headquarters manage
 * branches). Capacity cannot drop below the seats already filled.
 */
class SavePosition
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(?Position $position, array $data, User $actor): Position
    {
        foreach (['center_id', 'direct_manager_id', 'tech_manager_id', 'title_en', 'title_ar'] as $key) {
            $data[$key] = blank($data[$key] ?? null) ? null : $data[$key];
        }

        $validator = Validator::make($data, [
            'title_en' => ['nullable', 'string', 'min:3', 'max:255', 'required_without:title_ar'],
            'title_ar' => ['nullable', 'string', 'min:3', 'max:255'],
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'center_id' => ['nullable', 'integer', Rule::exists('centers', 'id')],
            'direct_manager_id' => ['nullable', 'integer', Rule::exists('positions', 'id'), Rule::notIn(array_filter([$position?->id]))],
            'tech_manager_id' => ['nullable', 'integer', Rule::exists('positions', 'id'), Rule::notIn(array_filter([$position?->id]))],
            'job_level_id' => ['required', 'integer', Rule::exists('job_levels', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'qualifications' => ['required', 'string', 'max:10000'],
            'responsibilities' => ['required', 'string', 'max:10000'],
            'max_emp' => ['required', 'integer', 'min:'.max(1, (int) $position?->exist_emp), 'max:500'],
            'is_active' => ['boolean'],
        ], [], $this->attributes());

        if ($validator->fails()) {
            throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn (array $list, string $key): array => ['form.'.$key => $list])->all());
        }

        $cityId = (int) $data['city_id'];

        $position === null
            ? Gate::forUser($actor)->authorize('create', [Position::class, $cityId])
            : Gate::forUser($actor)->authorize('update', $position);

        if (! HrAccess::canAccessCity($actor, $cityId)) {
            throw ValidationException::withMessages(['form.city_id' => __('hr_positions.errors.city_scope')]);
        }

        if ($data['center_id'] !== null && ! City::isNational($cityId)) {
            $centerOk = Center::query()->whereKey((int) $data['center_id'])
                ->where(fn ($query) => $query->where('city_id', $cityId)->orWhereNull('city_id'))
                ->exists();

            if (! $centerOk) {
                throw ValidationException::withMessages(['form.center_id' => __('hr_positions.errors.center_city')]);
            }
        }

        $values = [
            'title_en' => $data['title_en'] === null ? null : trim((string) $data['title_en']),
            'title_ar' => $data['title_ar'] === null ? null : trim((string) $data['title_ar']),
            'program_id' => (int) $data['program_id'],
            'city_id' => $cityId,
            'center_id' => $data['center_id'] === null ? null : (int) $data['center_id'],
            'direct_manager_id' => $data['direct_manager_id'] === null ? null : (int) $data['direct_manager_id'],
            'tech_manager_id' => $data['tech_manager_id'] === null ? null : (int) $data['tech_manager_id'],
            'job_level_id' => (int) $data['job_level_id'],
            'department_id' => (int) $data['department_id'],
            'qualifications' => trim((string) $data['qualifications']),
            'responsibilities' => trim((string) $data['responsibilities']),
            'max_emp' => (int) $data['max_emp'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        $target = $position ?? new Position;
        $target->forceFill($values)->save();

        return $target;
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        $names = [];

        foreach (array_keys((array) __('hr_positions.fields')) as $field) {
            $names[$field] = __('hr_positions.fields.'.$field);
        }

        return $names;
    }
}
