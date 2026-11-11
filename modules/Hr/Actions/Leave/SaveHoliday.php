<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Leave\HolidayProjects;

/**
 * Create or change an official holiday, a one-off grant or the birthday
 * window (team-coverage-contract §6.1). National HR sets anything; a program
 * manager only project holidays and grants on their own projects (H-6). A
 * grant goes to projects and never repeats.
 */
final class SaveHoliday
{
    public function __construct(private readonly HolidayProjects $projects) {}

    /**
     * @param  array{name_en?: mixed, name_ar?: mixed, scope?: mixed, kind?: mixed, projects?: mixed, start_date?: mixed, end_date?: mixed, is_recurring?: mixed}  $input
     *
     * @throws ValidationException
     */
    public function handle(User $user, ?int $holidayId, array $input): Holiday
    {
        $holiday = $holidayId === null ? null : Holiday::query()->with('projects:id,holiday_id,program_id,city_id')->findOrFail($holidayId);

        $holiday === null
            ? Gate::forUser($user)->authorize('create', Holiday::class)
            : Gate::forUser($user)->authorize('update', $holiday);

        $data = $this->validate($user, $input);

        return DB::transaction(function () use ($user, $holiday, $data): Holiday {
            $holiday ??= new Holiday;

            $holiday->fill([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'scope' => $data['scope'],
                'kind' => $data['kind'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                // A one-off grant never repeats.
                'is_recurring' => $data['kind'] !== Holiday::KIND_GRANT && (bool) ($data['is_recurring'] ?? false),
            ]);

            if (! $holiday->exists) {
                $holiday->created_by = $user->id;
            }

            $holiday->save();
            $holiday->projects()->delete();

            if ($data['scope'] === Holiday::SCOPE_PROJECTS) {
                foreach (array_unique($data['projects'] ?? []) as $key) {
                    [$programId, $cityId] = array_map('intval', explode(':', (string) $key));
                    $holiday->projects()->create(['program_id' => $programId, 'city_id' => $cityId]);
                }
            }

            return $holiday;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name_en: string, name_ar: string, scope: string, kind: string, projects?: list<string>, start_date: string, end_date: string, is_recurring?: bool}
     *
     * @throws ValidationException
     */
    private function validate(User $user, array $input): array
    {
        $national = $this->projects->isNationalHr($user);
        $managed = $this->projects->managedBy($user)->keys()->all();

        $validator = Validator::make($input, [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in($national ? [Holiday::SCOPE_NATIONAL, Holiday::SCOPE_PROJECTS] : [Holiday::SCOPE_PROJECTS])],
            'kind' => ['required', Rule::in($national
                ? [Holiday::KIND_OFFICIAL, Holiday::KIND_GRANT, Holiday::KIND_BIRTHDAY]
                : [Holiday::KIND_OFFICIAL, Holiday::KIND_GRANT])],
            'projects' => ['array', Rule::requiredIf(fn (): bool => ($input['scope'] ?? null) === Holiday::SCOPE_PROJECTS)],
            'projects.*' => ['string', Rule::in($managed)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_recurring' => ['boolean'],
        ], [
            'projects.required' => __('hr_leave_calendar.holidays.projects_required'),
            'projects.*.in' => __('hr_leave_calendar.holidays.invalid_project'),
            'scope.in' => __('hr_leave_calendar.holidays.invalid_scope'),
            'kind.in' => __('hr_leave_calendar.holidays.invalid_kind'),
        ]);

        $validator->after(function ($validator) use ($input): void {
            if (($input['kind'] ?? null) === Holiday::KIND_GRANT && ($input['scope'] ?? null) !== Holiday::SCOPE_PROJECTS) {
                $validator->errors()->add('scope', __('hr_leave_calendar.holidays.grant_needs_projects'));
            }
        });

        /** @var array{name_en: string, name_ar: string, scope: string, kind: string, projects?: list<string>, start_date: string, end_date: string, is_recurring?: bool} */
        return $validator->validate();
    }
}
