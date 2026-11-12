<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\DonorReports;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Models\DonorReport;

/**
 * Name who delivers a donor report, or clear it, from the report's own page
 * (page review, 6 Nov 2026: every report read "Not assigned" and nothing on
 * the page could change it). The person then finds it in Donor Reports.
 *
 * - Who: `assign` on the report (funding-agreements.update, or
 *   reports.aggregate with sight of its proposal), only while it is not
 *   submitted.
 * - The person must be an active HR employee, as the builder requires.
 * - The change is logged by the model (activityEmployeeFields).
 */
final class AssignDonorReportResponsible
{
    public function handle(DonorReport $report, ?int $employeeId, User $user): DonorReport
    {
        Gate::forUser($user)->authorize('assign', $report);

        Validator::make(
            ['responsibleEmployeeId' => $employeeId],
            ['responsibleEmployeeId' => ['nullable', 'integer', Rule::exists(Employee::class, 'id')->where('is_active', true)]],
            ['responsibleEmployeeId.exists' => __('donor_reports.responsible_inactive')],
        )->validate();

        $report->update(['responsible_employee_id' => $employeeId]);

        return $report;
    }
}
