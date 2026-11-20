<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Timeline;

use App\Enums\Locale;
use App\Support\Modules\Contracts\TimelineSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/**
 * An employee's contracts on their timeline: when each one started and when
 * it ended or was terminated. Read from contracts, which are kept for good.
 * Seen by whoever may read the person's records (EmployeePolicy::viewRecords).
 */
final class ContractsTimelineSource implements TimelineSource
{
    public function key(): string
    {
        return 'contracts';
    }

    public function label(): string
    {
        return __('timeline.sources.contracts');
    }

    public function icon(): string
    {
        return 'document-text';
    }

    public function allows(User $viewer, int $employeeId): bool
    {
        return self::readsRecords($viewer, $employeeId);
    }

    public function entries(User $viewer, int $employeeId): array
    {
        $title = Locale::current() === Locale::Arabic ? 'title_ar' : 'title_en';
        $entries = [];

        foreach (Contract::query()->where('employee_id', $employeeId)->with('position:id,title_en,title_ar')->get() as $contract) {
            $position = trim((string) ($contract->position?->{$title} ?: $contract->position?->title_en));
            $position = $position === '' ? null : $position;

            if ($contract->start_date !== null) {
                $entries[] = ['date' => CarbonImmutable::parse($contract->start_date), 'title' => __('timeline.contracts.started'), 'detail' => $position, 'href' => null];
            }

            if ($contract->terminated_at !== null) {
                $entries[] = ['date' => CarbonImmutable::parse($contract->terminated_at), 'title' => __('timeline.contracts.terminated'), 'detail' => $position, 'href' => null];
            } elseif ($contract->end_date !== null && CarbonImmutable::parse($contract->end_date)->isPast()) {
                $entries[] = ['date' => CarbonImmutable::parse($contract->end_date), 'title' => __('timeline.contracts.ended'), 'detail' => $position, 'href' => null];
            }
        }

        return $entries;
    }

    /**
     * Whether the viewer may read this employee's records; shared by HR's
     * timeline sources.
     */
    public static function readsRecords(User $viewer, int $employeeId): bool
    {
        $employee = Employee::query()->find($employeeId);

        return $employee !== null && Gate::forUser($viewer)->allows('viewRecords', $employee);
    }
}
