<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Timeline;

use App\Support\Modules\Contracts\TimelineSource;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Models\RenewalJourney;

/**
 * Journeys on an employee's timeline: when onboarding, a renewal and an exit
 * started, and when each was completed. Read from the three journey tables.
 */
final class JourneysTimelineSource implements TimelineSource
{
    public function key(): string
    {
        return 'journeys';
    }

    public function label(): string
    {
        return __('timeline.sources.journeys');
    }

    public function icon(): string
    {
        return 'map';
    }

    public function allows(User $viewer, int $employeeId): bool
    {
        return ContractsTimelineSource::readsRecords($viewer, $employeeId);
    }

    public function entries(User $viewer, int $employeeId): array
    {
        $entries = [];

        foreach ([
            'onboarding' => OnboardingJourney::class,
            'renewal' => RenewalJourney::class,
            'offboarding' => OffboardingJourney::class,
        ] as $kind => $model) {
            foreach ($model::query()->where('employee_id', $employeeId)->get(['id', 'started_at', 'completed_at']) as $journey) {
                if ($journey->started_at !== null) {
                    $entries[] = ['date' => CarbonImmutable::parse($journey->started_at), 'title' => __("timeline.journeys.{$kind}_started"), 'detail' => null, 'href' => null];
                }

                if ($journey->completed_at !== null) {
                    $entries[] = ['date' => CarbonImmutable::parse($journey->completed_at), 'title' => __("timeline.journeys.{$kind}_completed"), 'detail' => null, 'href' => null];
                }
            }
        }

        return $entries;
    }
}
