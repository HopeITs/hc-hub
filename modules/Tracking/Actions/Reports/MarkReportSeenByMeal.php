<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Reports;

use Illuminate\Support\Facades\Gate;
use Modules\Tracking\Models\Report;

/**
 * Acknowledge that MEAL has reviewed a figure. One way on purpose.
 */
class MarkReportSeenByMeal
{
    public function handle(Report $report): void
    {
        Gate::authorize('markSeen', $report);

        $report->fill(['is_seen_by_meal' => true])->save();
    }
}
