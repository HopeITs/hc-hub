<?php

declare(strict_types=1);

namespace Modules\Hr\Support\News;

use Modules\Core\Models\User;
use Modules\Hr\Enums\News\AudienceType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Desk\HrViewer;

/**
 * What a reader is, for audience-targeted news: their own employee record,
 * and the governorate, centre, program and department of their current
 * position. A news item addressed to one of these reaches them; an account
 * with no employee record only sees news addressed to everyone.
 */
final class NewsViewer
{
    /**
     * @return array<string, list<int>> audience type => the ids this reader matches
     */
    public static function targetsOf(?User $user): array
    {
        if ($user === null || $user->employee_id === null) {
            return [];
        }

        $viewer = HrViewer::for($user);
        $position = $viewer->contract()?->position;
        $ids = fn (mixed ...$values): array => array_values(array_unique(array_filter(array_map(fn (mixed $value): int => (int) $value, $values))));
        $ownCity = Employee::query()->whereKey($user->employee_id)->value('city_id');

        return [
            AudienceType::Employees->value => $ids($user->employee_id),
            AudienceType::Cities->value => $ids($ownCity, $position?->city_id, $position?->center?->city_id),
            AudienceType::Centers->value => $ids($position?->center_id),
            AudienceType::Programs->value => $ids($position?->program_id),
            AudienceType::Departments->value => $ids($position?->department_id),
        ];
    }
}
