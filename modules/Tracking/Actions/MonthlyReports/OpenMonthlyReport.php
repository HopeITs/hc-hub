<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\MonthlyReports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Support\MonthlyReportGenerator;
use Modules\Tracking\Support\MonthlyReportLineBuilder;

/**
 * Open (prepare) one month of a project (contracts §13, D-08): any started
 * month inside the project period. The draft is created when it is missing
 * and its lines are built, so a month the daily prepare has not reached
 * still opens with its lines. Opening does not make the user the preparer;
 * the first save of figures does.
 */
final class OpenMonthlyReport
{
    public function __construct(
        private MonthlyReportGenerator $generator,
        private MonthlyReportLineBuilder $lines,
    ) {}

    public function handle(Project $project, CarbonImmutable $month): MonthlyReport
    {
        Gate::authorize('open', [MonthlyReport::class, $project]);

        $month = $month->startOfMonth();
        $refusal = self::refusal($project, $month);

        if ($refusal !== null) {
            throw ValidationException::withMessages(['month' => $refusal]);
        }

        $this->generator->generate($project, $month, $month);

        $report = $project->monthlyReports()->whereDate('period', $month->toDateString())->firstOrFail();
        $this->lines->build($report);

        return $report->refresh();
    }

    /**
     * Why the month cannot be opened, or null when it can: a month not begun
     * yet, a project with no start or end date (it has no period at all), or
     * a month outside the project period.
     *
     * The one place this rule lives. The Monthly Reports list asks it before
     * offering Fill, so it never shows a button this action would refuse.
     */
    public static function refusal(Project $project, CarbonImmutable $month): ?string
    {
        $month = $month->startOfMonth();

        if ($month->greaterThan(CarbonImmutable::today()->startOfMonth())) {
            return __('monthly_reports.future_month');
        }

        if ($project->start_date === null || $project->end_date === null) {
            return __('monthly_reports.project_has_no_period');
        }

        if ($month->lessThan(CarbonImmutable::parse($project->start_date)->startOfMonth())
            || $month->greaterThan(CarbonImmutable::parse($project->end_date)->startOfMonth())) {
            return __('monthly_reports.outside_period');
        }

        return null;
    }
}
