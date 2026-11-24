<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Recruitment\Access;

/**
 * Courses recorded on employee files. A course "counts" when it is approved,
 * in progress or finished; pending and rejected ones are shown by status but
 * left out of the totals. Scoped to the viewer's city, like every HR page.
 * Coverage is measured over people with a contract in force today.
 */
class TrainingReport
{
    /** courses.status */
    public const PENDING = 1;

    public const APPROVED = 2;

    public const REJECTED = 3;

    public const IN_PROGRESS = 4;

    public const FINISHED = 5;

    public const COUNTED = [self::APPROVED, self::IN_PROGRESS, self::FINISHED];

    public const UNTRAINED_LIMIT = 20;

    public function __construct(public readonly User $viewer, public readonly ?int $year = null, public readonly ?int $categoryId = null) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $courses = $this->courses()->get();
        $counted = $courses->whereIn('status', self::COUNTED);
        $total = $counted->count();

        $inForce = $this->peopleInForce();
        $trainedIds = $counted->pluck('employee_id')->unique();
        $untrained = $inForce->reject(fn (Employee $e): bool => $trainedIds->contains($e->id))->values();

        return [
            'year' => $this->year,
            'courses' => $total,
            'trained' => $trainedIds->intersect($inForce->pluck('id'))->count(),
            'cost' => round((float) $counted->sum('cost'), 2),
            'certificates' => $counted->where('has_certificate', true)->count(),
            'people' => $inForce->count(),
            'coveragePercent' => $inForce->isEmpty() ? null : round($trainedIds->intersect($inForce->pluck('id'))->count() / $inForce->count() * 100, 1),
            'byCategory' => $this->share($counted->groupBy(fn (Course $c): string => $c->category?->name ?: __('hr_reports.training.no_category')), $total),
            'byStatus' => $this->share($courses->groupBy(fn (Course $c): string => $this->statusLabel((int) $c->status)), $courses->count()),
            'byCity' => $this->share($counted->groupBy(fn (Course $c): string => $c->employee?->city?->name ?: '-'), $total),
            'byMonth' => $counted->filter(fn (Course $c): bool => $c->start_date !== null)
                ->groupBy(fn (Course $c): string => $c->start_date->format('Y-m'))->map->count()->sortKeys(),
            'byOrganization' => $counted->filter(fn (Course $c): bool => filled($c->organization))
                ->groupBy(fn (Course $c): string => trim((string) $c->organization))->map->count()->sortDesc()->take(8),
            'untrainedCount' => $untrained->count(),
            'untrained' => $untrained->map(fn (Employee $e): array => [
                'name' => $e->name,
                'username' => $e->username,
                'city' => $e->city?->name ?: '-',
            ])->values(),
        ];
    }

    /**
     * @return Builder<Course>
     */
    private function courses(): Builder
    {
        $cityIds = Access::activeCityIds($this->viewer);

        return Course::query()
            ->when($cityIds !== null, fn (Builder $q) => $q->whereHas('employee', fn (Builder $e) => $e->whereIn('city_id', $cityIds)))
            ->when($this->year, fn (Builder $q) => $q->whereYear('start_date', $this->year))
            ->when($this->categoryId, fn (Builder $q) => $q->where('category_id', $this->categoryId))
            ->with(['category', 'employee.city']);
    }

    /**
     * @return Collection<int, Employee> people with a contract in force today, inside the viewer's scope
     */
    private function peopleInForce(): Collection
    {
        $ids = Contract::query()->activeNow()->visibleTo($this->viewer)->pluck('employee_id')->unique();

        return Employee::query()->where('is_active', true)->whereIn('id', $ids)->with('city')->orderBy('id')->get();
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::PENDING => __('hr_reports.training.status.pending'),
            self::APPROVED => __('hr_reports.training.status.approved'),
            self::REJECTED => __('hr_reports.training.status.rejected'),
            self::IN_PROGRESS => __('hr_reports.training.status.in_progress'),
            self::FINISHED => __('hr_reports.training.status.finished'),
            default => '-',
        };
    }

    /**
     * @param  Collection<string, Collection<int, Course>>  $groups
     * @return Collection<int, array{label: string, count: int, percent: float}>
     */
    private function share(Collection $groups, int $total): Collection
    {
        return $groups->map(fn (Collection $g, string $label): array => [
            'label' => $label,
            'count' => $g->count(),
            'percent' => $total ? round($g->count() / $total * 100, 1) : 0.0,
        ])->sortByDesc('count')->values();
    }
}
