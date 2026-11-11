<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * Create or update an employee record, as a draft or as a complete record.
 *
 * A new record is never active: an employee becomes active when their first
 * real contract is saved (ActivateEmployeeForContract). A draft becomes a
 * complete record the first time it is saved complete, and never goes back.
 */
class SaveEmployee
{
    /**
     * @param  array<string, mixed>  $data  validated employee columns
     */
    public function handle(?Employee $employee, array $data, bool $complete, User $actor, ?UploadedFile $photo = null): Employee
    {
        $cityId = (int) ($data['city_id'] ?? 0);

        if ($employee !== null) {
            Gate::forUser($actor)->authorize('update', $employee);
            abort_if(! $complete && ! $employee->isDraft(), 403);
        } else {
            Gate::forUser($actor)->authorize('create', [Employee::class, $cityId]);
        }

        if (! HrAccess::canAccessCity($actor, $cityId)) {
            throw ValidationException::withMessages(['city_id' => __('hr_employees.validation.city_scope')]);
        }

        $employee = DB::transaction(function () use ($employee, $data, $complete, $actor): Employee {
            if ($employee !== null) {
                $employee->fill($data);

                if ($complete && $employee->isDraft()) {
                    $employee->record_status = Employee::RECORD_COMPLETE;
                }
            } else {
                $employee = new Employee($data);
                $employee->forceFill([
                    'record_status' => $complete ? Employee::RECORD_COMPLETE : Employee::RECORD_DRAFT,
                    'is_active' => false,
                ]);
            }

            if (! $employee->isDraft()) {
                $employee->digital_id_token ??= Str::random(64);
            }

            if ($employee->isDirty()) {
                $employee->last_saved_by = $actor->id;
            }

            $employee->save();

            return $employee;
        });

        if ($photo !== null) {
            HrMedia::clear(HrMedia::EMPLOYEE, (int) $employee->id, 'avatar');
            HrMedia::store($photo, HrMedia::EMPLOYEE, (int) $employee->id, 'avatar');
            Employee::forgetAvatars();
        }

        Employee::forgetPickerOptions();

        return $employee;
    }
}
