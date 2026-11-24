<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Reports;

use Closure;
use Modules\Core\Models\User;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Support\Recruitment\Access;

/**
 * The reports the hub page lists, and who may open each. Every report is for
 * HR and the head-office roles AND the domain permission its data already
 * needs, so the hub never widens access: a report is only a summary of a page
 * the viewer could already open. Each page re-checks through allows(); hiding
 * a card is not the gate.
 */
final class ReportCatalog
{
    /**
     * HR (any city) and the administrator roles. Cities are scoped by each report.
     */
    public static function isReportViewer(?User $user): bool
    {
        return $user !== null
            && Access::activeRoleIs($user, 'HR', ...(array) config('access-control.administrator_roles', []));
    }

    /**
     * @return array<string, array{route: string, allows: Closure(User): bool}>
     */
    public static function all(): array
    {
        return [
            'leaving-reasons' => [
                'route' => 'hr.reports.leaving-reasons',
                'allows' => fn (User $user): bool => Access::hasActivePermission($user, 'contracts.view'),
            ],
            'recruitment-funnel' => [
                'route' => 'hr.reports.recruitment-funnel',
                'allows' => fn (User $user): bool => $user->can('viewAny', RecruitmentApplicant::class),
            ],
            'leave' => [
                'route' => 'hr.reports.leave',
                'allows' => fn (User $user): bool => Access::hasActivePermission($user, 'leaves.view-all'),
            ],
            'vacancies' => [
                'route' => 'hr.reports.vacancies',
                'allows' => fn (User $user): bool => Access::hasActivePermission($user, 'positions.view'),
            ],
            'training' => [
                'route' => 'hr.reports.training',
                'allows' => fn (User $user): bool => Access::hasActivePermission($user, 'employees.view'),
            ],
            'profile-completeness' => [
                'route' => 'hr.reports.profile-completeness',
                'allows' => fn (User $user): bool => Access::hasActivePermission($user, 'employees.view'),
            ],
        ];
    }

    public static function title(string $key): string
    {
        return __('hr_reports.reports.'.$key.'.title');
    }

    public static function lead(string $key): string
    {
        return __('hr_reports.reports.'.$key.'.lead');
    }

    public static function allows(string $key, ?User $user): bool
    {
        $report = self::all()[$key] ?? null;

        return $report !== null && $user !== null && self::isReportViewer($user) && $report['allows']($user);
    }

    /**
     * @return array<string, array{route: string, allows: Closure(User): bool}>
     */
    public static function visibleTo(?User $user): array
    {
        return array_filter(self::all(), fn (array $report, string $key): bool => self::allows($key, $user), ARRAY_FILTER_USE_BOTH);
    }
}
