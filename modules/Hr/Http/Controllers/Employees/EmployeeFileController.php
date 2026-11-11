<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Experience;
use Modules\Hr\Support\Employees\HrMedia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download one of an employee's stored documents: an attachment, or the file
 * of an education, experience or course row. Only the employee themselves and
 * HR in their city may (EmployeePolicy::viewPrivateDocuments).
 */
class EmployeeFileController extends Controller
{
    public function __invoke(Request $request, int $media): Response
    {
        $file = HrMedia::find($media);

        abort_if($file === null, 404);

        $employeeId = match (true) {
            $file->model_type === HrMedia::EMPLOYEE && $file->collection_name === 'attachments' => (int) $file->model_id,
            $file->model_type === HrMedia::EDUCATION => Education::query()->whereKey($file->model_id)->value('employee_id'),
            $file->model_type === HrMedia::EXPERIENCE => Experience::query()->whereKey($file->model_id)->value('employee_id'),
            $file->model_type === HrMedia::COURSE => Course::query()->whereKey($file->model_id)->value('employee_id'),
            default => null,
        };

        abort_if($employeeId === null, 404);

        $employee = Employee::query()->select(['id', 'city_id', 'is_active'])->findOrFail((int) $employeeId);

        Gate::authorize('viewPrivateDocuments', $employee);

        return HrMedia::response($file, inline: $request->boolean('inline'));
    }
}
