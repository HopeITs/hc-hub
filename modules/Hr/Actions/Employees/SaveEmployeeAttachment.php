<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * Add, rename, replace or remove one of an employee's documents (the
 * `attachments` collection: ID copies, certificates, signed papers).
 */
class SaveEmployeeAttachment
{
    /** The document categories the HR app offered. */
    public const CATEGORIES = ['personal_documents', 'education_certificate', 'experience_certificate', 'cv', 'contract', 'other'];

    public function handle(Employee $employee, ?int $mediaId, string $name, string $category, ?UploadedFile $file = null): void
    {
        Gate::authorize('update', $employee);

        $properties = ['name' => $name, 'category' => $category];

        if ($mediaId !== null) {
            $this->ownedBy($employee, $mediaId);
        }

        if ($file !== null) {
            HrMedia::store($file, HrMedia::EMPLOYEE, (int) $employee->id, 'attachments', $properties, $name);

            if ($mediaId !== null) {
                HrMedia::delete($mediaId);
            }

            return;
        }

        if ($mediaId !== null) {
            HrMedia::updateProperties($mediaId, $properties, $name);
        }
    }

    public function delete(Employee $employee, int $mediaId): void
    {
        Gate::authorize('update', $employee);

        $this->ownedBy($employee, $mediaId);
        HrMedia::delete($mediaId);
    }

    private function ownedBy(Employee $employee, int $mediaId): void
    {
        $media = HrMedia::find($mediaId);

        abort_unless(
            $media !== null
            && $media->model_type === HrMedia::EMPLOYEE
            && (int) $media->model_id === (int) $employee->id
            && $media->collection_name === 'attachments',
            404,
        );
    }
}
