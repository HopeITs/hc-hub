<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Closure;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentApproverEligibility;
use Modules\Hr\Models\InAppFeedback;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentJobOffer;
use Modules\Hr\Support\Assistant\HrAssistant;
use Modules\Hr\Support\Inbox\WorkInbox;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/**
 * The HR pages of the sidebar: every HR page with the permission that opens
 * it and the sidebar group it sits in. App\Support\Desk\Destinations reads
 * this into its own table, so the sidebar, the Ask bar, Desk buttons and
 * Quick access all offer an HR page by the same rule.
 *
 * HR has three groups of its own (people, leave, recruitment); a page that
 * repeats an idea the hub already has joins that group instead: the inbox
 * with the Desk, the viewer's own leaves and folders with their workspace,
 * HR settings and the HR archive with the administration.
 *
 * A page whose route does not exist (an area still being built) is left out,
 * so no link ever points nowhere. Labels are hr_nav.*; `hero` is a
 * resources/views/flux/icon/nav-* icon, `icon` one of the Desk's own.
 */
final class HrDestinations
{
    /**
     * @return list<array{route: string, label: string, icon: string, hero: string, group: string, allows: Closure(User): bool, bindsAdministrators: bool, under: string|null}>
     */
    public function pages(): array
    {
        $can = fn (string ...$abilities): Closure => fn (User $user): bool => $user->canAny($abilities);
        $staff = fn (User $user): bool => $user->employee_id !== null;

        $pages = [
            // What waits for the viewer sits with their Desk and reviews.
            ['hr.inbox', 'inbox', 'flag', 'nav-inbox', 'overview', fn (User $user): bool => app(WorkInbox::class)->hasSources($user)],

            ['hr.employees.index', 'employees', 'users', 'nav-users', 'hr_people', $can('employees.view')],
            ['hr.contracts.index', 'contracts', 'file', 'nav-file-text', 'hr_people', $can('contracts.view')],
            ['hr.positions.index', 'positions', 'grid', 'nav-layers', 'hr_people', $can('positions.view')],
            ['hr.positions.team', 'team_staffing', 'users', 'nav-network', 'hr_people', $can('positions.view')],
            ['hr.badges.index', 'badges', 'check', 'nav-shield-check', 'hr_people', $can('badges.view')],
            // One link for the three statistics pages: each links to the other two.
            ['hr.statistics.general', 'statistics', 'chart', 'nav-chart-line', 'hr_people', $can('statistics.view')],
            ['hr.statistics.contracts', 'statistics_contracts', 'chart', 'nav-file-text', 'hr_people', $can('statistics.view'), 'under' => 'hr.statistics.general'],
            ['hr.statistics.salaries', 'statistics_salaries', 'chart', 'nav-chart-line', 'hr_people', $can('statistics.view'), 'under' => 'hr.statistics.general'],

            ['hr.leave.team', 'team_leaves', 'cal', 'nav-calendar-check', 'hr_leave', $can('leaves.view-all')],
            ['hr.leave.balances', 'leave_balances', 'grid', 'nav-gauge', 'hr_leave', $can('leaves.view-all')],
            ['hr.leave.holidays', 'holidays', 'cal', 'nav-calendar', 'hr_leave', $can('project-basics.view')],

            ['hr.recruitment.requests.index', 'recruitment_requests', 'file', 'nav-file-plus', 'hr_recruitment', $can('recruitment-requests.view')],
            ['hr.recruitment.approvals.pending', 'recruitment_approvals', 'check', 'nav-list-checks', 'hr_recruitment', fn (User $user): bool => Access::hasActivePermission($user, 'recruitment-requests.approve') && self::mayReceiveApprovals($user), 'own' => true],
            ['hr.recruitment.campaigns.index', 'vacancies', 'search', 'nav-clipboard-list', 'hr_recruitment', $can('recruitment-vacancies.view')],
            ['hr.recruitment.applicants.index', 'applicants', 'users', 'nav-circle-user', 'hr_recruitment', fn (User $user): bool => $user->can('viewAny', RecruitmentApplicant::class)],
            ['hr.recruitment.interviews.index', 'interviews', 'cal', 'nav-calendar-range', 'hr_recruitment', fn (User $user): bool => $user->can('viewAny', RecruitmentInterview::class)],
            ['hr.recruitment.offers.index', 'job_offers', 'file', 'nav-handshake', 'hr_recruitment', fn (User $user): bool => $user->can('viewAny', RecruitmentJobOffer::class)],
            ['hr.recruitment.indicator-rules', 'indicator_rules', 'grid', 'nav-sliders-horizontal', 'hr_recruitment', fn (User $user): bool => Access::hasActivePermission($user, 'recruitment-indicators.manage')],

            // The viewer's own HR pages join their tasks and calendar.
            ['hr.leave.team-calendar', 'team', 'users', 'nav-calendar-range', 'workspace', $can('leaves.view-own', 'leaves.view-all')],
            ['hr.leave.index', 'leave_requests', 'cal', 'nav-tree-palm', 'workspace', fn (User $user): bool => $staff($user) && $user->can('leaves.view-own'), 'own' => true],
            ['hr.checklists.index', 'checklists', 'check', 'nav-list-checks', 'workspace', $can('checklists.view-own', 'checklists.view-all', 'checklists.manage')],
            ['hr.archives.mine', 'my_folders', 'file', 'nav-folder-open', 'workspace', fn (User $user): bool => $staff($user) && $user->can('archives.view'), 'own' => true],
            ['hr.news.index', 'news', 'bell', 'nav-book-open', 'workspace', $can('news.view')],
            ['hr.assistant', 'assistant', 'info', 'nav-lightbulb', 'workspace', fn (User $user): bool => $staff($user) && app(HrAssistant::class)->isAvailable(), 'own' => true],
            ['hr.feedback.index', 'feedback', 'info', 'nav-message-circle-question-mark', 'workspace', fn (User $user): bool => $staff($user) || $user->can('viewAny', InAppFeedback::class)],

            // HR's settings sit with the rest of the administration; the old HR log is opened from the Activity log.
            ['hr.settings.index', 'settings', 'grid', 'nav-settings', 'administration', $can('project-basics.view')],
            ['hr.activity.index', 'activity', 'undo', 'nav-file-clock', 'administration', $can('activity-log.view'), 'under' => 'activity.index'],

            ['hr.employees.me', 'my_profile', 'users', 'nav-circle-user', 'you', $staff, 'own' => true],
        ];

        $offered = [];

        // `own` marks a page about the viewer themself (their leaves, folders, approvals): it binds
        // administrators too, who would otherwise see every link even without an employee record.
        // `under` names the sidebar link a page is reached through instead of having one of its own.
        foreach ($pages as $page) {
            [$route, $label, $icon, $hero, $group, $allows] = $page;

            if (Route::has($route)) {
                $offered[] = ['route' => $route, 'label' => 'hr_nav.'.$label, 'icon' => $icon, 'hero' => $hero, 'group' => $group, 'allows' => $allows, 'bindsAdministrators' => $page['own'] ?? false, 'under' => $page['under'] ?? null];
            }
        }

        return $offered;
    }

    /**
     * The approvals page's own gate: eligibility reads the recruitment
     * config, which must be loaded first or the answer differs from the page's.
     * Memoized per request: the desk asks it several times per page (3 queries each).
     */
    private static function mayReceiveApprovals(User $user): bool
    {
        return once(static function () use ($user): bool {
            RecruitmentConfig::load();

            return app(RecruitmentApproverEligibility::class)->mayReceiveApprovals($user);
        });
    }
}
