<?php

declare(strict_types=1);

namespace Modules\Tracking\Policies;

use Modules\Core\Models\User;
use Modules\Tracking\Enums\DonorReportType;
use Modules\Tracking\Models\DonorReport;

/**
 * Donor reports (contracts §14.3). The responsible person is the employee
 * named on the report (`responsible_employee_id` = the user's
 * `employee_id`, both known); they can deliver it without any money
 * permission. A submitted report is frozen.
 */
class DonorReportPolicy
{
    /**
     * Open the register of donor reports: anyone who may view one, or the
     * responsible person on at least one.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('funding-agreements.view') || $user->can('reports.aggregate') || $user->hasReportingDuties();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DonorReport $donorReport): bool
    {
        return $user->can('funding-agreements.view') || $user->can('reports.aggregate') || $donorReport->isResponsible($user);
    }

    /**
     * Mark the report as submitted to its reader.
     */
    public function submit(User $user, DonorReport $donorReport): bool
    {
        return $donorReport->isResponsible($user) || $user->can('funding-agreements.update');
    }

    /**
     * Name who delivers the report, while it is not submitted (a submitted
     * report is frozen): agreement editors, and report compilers who may see
     * its proposal. A compiler working from an assigned duty alone cannot
     * hand the report to someone else, as in the builder.
     */
    public function assign(User $user, DonorReport $donorReport): bool
    {
        if ($donorReport->isSubmitted()) {
            return false;
        }

        if ($user->can('funding-agreements.update')) {
            return true;
        }

        $proposal = $donorReport->donorProposal?->proposal;

        return $user->can('reports.aggregate') && $proposal !== null && $user->can('view', $proposal);
    }

    /**
     * Attach a delivered report file.
     */
    public function uploadFile(User $user, DonorReport $donorReport): bool
    {
        return $this->submit($user, $donorReport);
    }

    /**
     * Remove a delivered report file.
     */
    public function deleteFile(User $user, DonorReport $donorReport): bool
    {
        return $this->submit($user, $donorReport);
    }

    /**
     * Download a delivered report file.
     */
    public function downloadFile(User $user, DonorReport $donorReport): bool
    {
        return $donorReport->isResponsible($user) || $user->can('funding-agreements.view');
    }

    /**
     * Compose a new version from today's validated figures. Only a composed
     * report that has not gone out can be regenerated.
     */
    public function regenerate(User $user, DonorReport $donorReport): bool
    {
        if ($user->cannot('reports.aggregate') || ! $donorReport->isComposed() || $donorReport->isSubmitted()) {
            return false;
        }

        $proposal = $donorReport->donorProposal?->proposal;

        return ($proposal !== null && $user->can('view', $proposal))
            || ($donorReport->type === DonorReportType::Narrative && $donorReport->isResponsible($user));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DonorReport $donorReport): bool
    {
        return $user->can('funding-agreements.update') && ! $donorReport->isSubmitted();
    }
}
