<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Reports;

use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Enums\ReportStatus;
use Modules\Tracking\Models\Report;

/**
 * Submit a draft for review.
 *
 * Writes status and submit_date together so the two can never disagree about
 * whether, and when, a report was submitted.
 */
class SubmitReportForReview
{
    /**
     * @return bool false when the report is not a draft and nothing changed
     */
    public function handle(Report $report): bool
    {
        if ($report->status !== ReportStatus::Draft) {
            return false;
        }

        Gate::authorize('submit', $report);

        $report->fill([
            'status' => ReportStatus::Reviewed,
            'submit_date' => now()->toDateString(),
        ])->save();

        return true;
    }
}
