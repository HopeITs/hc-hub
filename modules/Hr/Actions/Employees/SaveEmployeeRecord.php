<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * Add or change one row of an employee's record: an education, a past job
 * (experience), a course or a relative. Education, experience and course
 * rows may carry one supporting file.
 */
class SaveEmployeeRecord
{
    /** kind => [relation, hr_media model type or null]. */
    public const KINDS = [
        'education' => ['educations', HrMedia::EDUCATION],
        'experience' => ['experiences', HrMedia::EXPERIENCE],
        'course' => ['courses', HrMedia::COURSE],
        'relative' => ['relatives', null],
    ];

    /**
     * @param  array<string, mixed>  $data  validated columns of the row
     */
    public function handle(Employee $employee, string $kind, ?int $id, array $data, ?UploadedFile $file = null): Model
    {
        Gate::authorize('update', $employee);

        [$relation, $mediaType] = self::KINDS[$kind] ?? throw new InvalidArgumentException("Unknown employee record kind [{$kind}].");

        $row = $id === null
            ? $employee->{$relation}()->create($data)
            : tap($employee->{$relation}()->findOrFail($id))->update($data);

        if ($file !== null && $mediaType !== null) {
            HrMedia::clear($mediaType, (int) $row->getKey(), 'file');
            HrMedia::store($file, $mediaType, (int) $row->getKey(), 'file');
        }

        return $row;
    }

    public function delete(Employee $employee, string $kind, int $id): void
    {
        Gate::authorize('update', $employee);

        [$relation, $mediaType] = self::KINDS[$kind] ?? throw new InvalidArgumentException("Unknown employee record kind [{$kind}].");

        $row = $employee->{$relation}()->findOrFail($id);

        if ($mediaType !== null) {
            HrMedia::clear($mediaType, (int) $row->getKey(), 'file');
        }

        $row->delete();
    }
}
