<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Closure;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Models\InAppFeedback;
use Modules\Hr\Support\Assistant\HrAssistant;
use Modules\Hr\Support\Inbox\WorkInbox;

/**
 * The HR section of the sidebar: every HR page with the permission that
 * opens it. App\Support\Desk\Destinations reads this into its own table (the
 * "hr" group), so the sidebar, the Ask bar, Desk buttons and Quick access
 * all offer an HR page by the same rule.
 *
 * A page whose route does not exist (an area still being built) is left out,
 * so no link ever points nowhere. Labels are hr_nav.*; `hero` is a
 * resources/views/flux/icon/nav-* icon, `icon` one of the Desk's own.
 */
final class HrDestinations
{
    /**
     * @return list<array{route: string, label: string, icon: string, hero: string, allows: Closure(User): bool}>
     */
    public function pages(): array
    {
        $can = fn (string ...$abilities): Closure => fn (User $user): bool => $user->canAny($abilities);
        $staff = fn (User $user): bool => $user->employee_id !== null;

        $pages = [
            ['hr.inbox', 'inbox', 'flag', 'nav-inbox', fn (User $user): bool => app(WorkInbox::class)->hasSources($user)],
            ['hr.employees.index', 'employees', 'users', 'nav-users', $can('employees.view')],
            ['hr.contracts.index', 'contracts', 'file', 'nav-file-text', $can('contracts.view')],
            ['hr.positions.index', 'positions', 'grid', 'nav-network', $can('positions.view')],
            ['hr.positions.team', 'team_staffing', 'users', 'nav-network', $can('positions.view')],
            ['hr.badges.index', 'badges', 'check', 'nav-list-checks', $can('badges.view')],
            ['hr.archives.mine', 'my_folders', 'file', 'nav-file-text', fn (User $user): bool => $staff($user) && $user->can('archives.view')],
            ['hr.employees.me', 'my_profile', 'users', 'nav-users', $staff],
            ['hr.team.index', 'team', 'users', 'nav-calendar-range', fn (User $user): bool => $staff($user) && $user->can('leaves.view-own')],
            ['hr.leave.requests.index', 'leave_requests', 'cal', 'nav-calendar-check', $can('leaves.view-own', 'leaves.view-all')],
            ['hr.leave.holidays.index', 'holidays', 'cal', 'nav-calendar', $can('project-basics.view')],
            ['hr.recruitment.requests.index', 'recruitment_requests', 'file', 'nav-file-plus', $can('recruitment-requests.view')],
            ['hr.recruitment.vacancies.index', 'vacancies', 'search', 'nav-clipboard-list', $can('recruitment-vacancies.view')],
            ['hr.checklists.index', 'checklists', 'check', 'nav-list-checks', $can('checklists.view-own', 'checklists.view-all', 'checklists.manage')],
            ['hr.news.index', 'news', 'bell', 'nav-book-open', $can('news.view')],
            ['hr.statistics.index', 'statistics', 'chart', 'nav-chart-line', $can('statistics.view')],
            ['hr.assistant', 'assistant', 'info', 'nav-lightbulb', fn (User $user): bool => $staff($user) && app(HrAssistant::class)->isAvailable()],
            ['hr.activity.index', 'activity', 'undo', 'nav-file-clock', $can('activity-log.view')],
            ['hr.feedback.index', 'feedback', 'info', 'nav-message-circle-question-mark', fn (User $user): bool => $staff($user) || $user->can('viewAny', InAppFeedback::class)],
            ['hr.settings.index', 'settings', 'grid', 'nav-settings', $can('project-basics.view')],
        ];

        $offered = [];

        foreach ($pages as [$route, $label, $icon, $hero, $allows]) {
            if (Route::has($route)) {
                $offered[] = ['route' => $route, 'label' => 'hr_nav.'.$label, 'icon' => $icon, 'hero' => $hero, 'allows' => $allows];
            }
        }

        return $offered;
    }
}
