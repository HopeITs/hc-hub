<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Reports;

use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Enums\ReportStatus;
use Modules\Tracking\Models\Report;

/**
 * Approve a reviewed report. Only valid from Reviewed.
 */
class ApproveReport
{
    /**
     * @return bool false when the report is not under review and nothing changed
     */
    public function handle(Report $report): bool
    {
        if ($report->status !== ReportStatus::Reviewed) {
            return false;
        }

        Gate::authorize('approve', $report);

        $report->fill(['status' => ReportStatus::Approved])->save();

        return true;
    }
}
