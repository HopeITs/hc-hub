<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\MonthlyReports\EditsMonthlyReportActivities;
use Modules\Tracking\Livewire\Concerns\MonthlyReports\EditsMonthlyReportLines;
use Modules\Tracking\Livewire\Concerns\MonthlyReports\ManagesMonthlyReportFiles;
use Modules\Tracking\Livewire\Concerns\MonthlyReports\ReadsMonthlyReport;
use Modules\Tracking\Livewire\Concerns\MonthlyReports\RunsMonthlyReportWorkflow;

/**
 * Stands in for monthly-reports/show.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the traits are never analysed.
 * Never instantiated.
 */
abstract class MonthlyReportShowPageHost extends Component
{
    use EditsMonthlyReportActivities;
    use EditsMonthlyReportLines;
    use ManagesMonthlyReportFiles;
    use ReadsMonthlyReport;
    use RunsMonthlyReportWorkflow;
}
