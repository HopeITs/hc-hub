<?php

namespace App\Support;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Throwable;

/**
 * The sidebar's structure (C0, docs/ux/interaction-contract.md §4): the work
 * inbox on top, then My space, My team, HR operations, Settings and, last,
 * Organisation (reference pages).
 *
 * Every item is shown under the same permission check the old sidebar used;
 * the areas only decide *where* an item sits for the active role. Nothing
 * here grants or removes access: every route keeps its own middleware and
 * policies (C0-Q4). The breadcrumbs read the same map, so a page's first
 * crumb is the area it sits in for that role.
 *
 * An item is ['id', 'label', 'route', 'active' (routeIs patterns), 'icon',
 * 'tour'?, 'counter'?] or a group ['id', 'label', 'icon', 'children'].
 */
final class Navigation
{
    /**
     * @return array{inbox: array{count: int, route: string}|null, areas: list<array{id: string, label: string, items: list<array<string, mixed>>}>}
     */
    public static function for(User $user, bool $isDeliverResponsible = false): array
    {
        $can = fn (string ...$permissions): bool => collect($permissions)->contains(fn ($p) => $user->hasActivePermission($p));
        $isManager = $user->activeRoleIs('manager');

        // A role that may change reference data finds it with its work
        // (HR operations / Settings); one that only reads it, under Organisation.
        $editsPrograms = $can('programs.create', 'programs.update');
        $editsPositions = $can('positions.create', 'positions.update');
        $editsCenters = $can('centers.create', 'centers.update');
        $editsBasics = $can('project-basics.create', 'project-basics.update', 'project-basics.delete');

        $mySpace = array_values(array_filter([
            self::item('nav.my-space.home', __('general.dashboard'), 'dashboard', ['dashboard', 'employee.dashboard'], 'dashboard', 'nav.dashboard'),
            $can('leaves.view-own') ? self::item('nav.my-space.leaves', __('general.my-leaves'), 'leaves.index', ['leaves.index'], 'my-leaves', 'nav.my-leaves') : null,
            $can('checklists.view-own') ? self::item('nav.my-space.items', __('general.my-items'), 'checklist-items.index', ['checklist-items.index'], 'clipboard-list', 'nav.my-items') : null,
            $can('archives.view') ? self::item('nav.my-space.files', __('general.my-folders'), 'my-folders.index', ['my-folders.*'], 'archived', 'nav.archives') : null,
            $can('archives.view') ? self::item('nav.my-space.shared-files', __('general.shared-folders'), 'shared-folders.index', ['shared-folders.*'], 'folder') : null,
        ]));

        $basics = self::group('nav.basics', __('general.project-basics'), 'home', [
            self::item('nav.basics.cities', trans_choice('general.city', 2), 'cities.index', ['cities.*']),
            self::item('nav.basics.contract-types', trans_choice('general.contract-type', 2), 'contract-types.index', ['contract-types.*']),
            self::item('nav.basics.leave-types', trans_choice('general.leave-type', 2), 'leave-types.index', ['leave-types.*']),
            self::item('nav.basics.annual-leave-rules', __('general.annual_leave_rules'), 'annual-leave-rules.index', ['annual-leave-rules.*']),
            self::item('nav.basics.termination-reasons', __('general.termination_reasons'), 'termination-reasons.index', ['termination-reasons.*']),
            self::item('nav.basics.checklist-items', trans_choice('general.checklist-items', 2), 'basic-checklist-items.index', ['basic-checklist-items.*']),
            self::item('nav.basics.job-levels', trans_choice('general.job-level', 2), 'job-levels.index', ['job-levels.*']),
            self::item('nav.basics.departments', trans_choice('general.department', 2), 'departments.index', ['departments.*']),
            self::item('nav.basics.course-categories', trans_choice('general.course-category', 2), 'course-categories.index', ['course-categories.*']),
        ], 'nav.project-basics');

        $programs = self::item('nav.programs', trans_choice('general.program', 2), 'programs.index', ['programs.*'], 'program', 'nav.programs');
        $positions = self::item('nav.positions', __('general.positions'), 'positions.index', ['positions.*'], 'layers');
        $centers = self::item('nav.centers', trans_choice('general.center', 2), 'centers.index', ['centers.*'], 'center', 'nav.centers');
        $news = self::item('nav.news', __('general.news-management'), 'news.index', ['news.*'], 'newspaper', 'nav.news');

        $hrOps = array_values(array_filter([
            $can('employees.view') ? self::item('nav.hr.employees', trans_choice('general.employee', 2), 'employees.index', ['employees.*'], 'users', 'nav.employees') : null,
            ! $isManager && $can('contracts.view') ? self::item('nav.hr.contracts', trans_choice('general.contracts', 2), 'contracts.index', ['contracts.*'], 'contracts') : null,
            ! $isManager && $can('leaves.view-all') ? self::item('nav.hr.leaves', __('general.all-leaves'), 'leaves.all', ['leaves.all'], 'calendar', 'nav.leaves-management') : null,
            $can('leaves.manage-balances') ? self::item('nav.hr.balances', __('general.balance'), 'leaves.balance', ['leaves.balance'], 'calendar') : null,
            $can('checklists.manage') ? self::item('nav.hr.deliverables', trans_choice('general.default-deliverables', 2), 'default-deliverables.index', ['default-deliverables.*'], 'checklist') : null,
            $isDeliverResponsible && $can('checklists.manage') ? self::item('nav.hr.done-requests', __('general.done_requests'), 'checklist-items.done-requests', ['checklist-items.done-requests'], 'check') : null,
            $editsPrograms && $can('programs.view') ? $programs : null,
            $editsPositions && $can('positions.view') ? $positions : null,
            $editsCenters && $can('centers.view') ? $centers : null,
            $can('badges.view') ? self::item('nav.hr.badges', __('general.all-badges'), 'badges.index', ['badges.*'], 'badges', 'nav.badges') : null,
            $can('statistics.view') ? self::group('nav.hr.statistics', __('general.statistics'), 'statistics', [
                self::item('nav.hr.statistics.general', __('general.general-statistics'), 'statistics.general', ['statistics.general']),
                self::item('nav.hr.statistics.contracts', __('general.contract-statistics'), 'statistics.contract', ['statistics.contract']),
            ], 'nav.statistics') : null,
        ]));

        // News sits with HR's work when there is any; a manager, who has no
        // HR operations, finds it under Organisation instead.
        $newsInHr = $can('news.view') && $hrOps !== [];
        if ($newsInHr) {
            $hrOps[] = $news;
        }

        $organisation = array_values(array_filter([
            $can('positions.view') ? self::item('nav.org.chart', __('general.orgn-chart'), 'org.chart', ['org.chart'], 'code-fork', 'nav.org-chart') : null,
            ! $editsCenters && $can('centers.view') ? $centers : null,
            ! $editsPrograms && $can('programs.view') ? $programs : null,
            ! $editsPositions && $can('positions.view') ? $positions : null,
            ! $editsBasics && $can('project-basics.view') ? $basics : null,
            ! $newsInHr && $can('news.view') ? $news : null,
        ]));

        $myTeam = array_values(array_filter([
            $isManager && $can('leaves.view-all') ? self::item('nav.my-team.leaves', __('general.nav.team-leaves'), 'leaves.all', ['leaves.all'], 'calendar', 'nav.leaves-management') : null,
            $isManager && $can('contracts.view') ? self::item('nav.my-team.contracts', __('general.nav.team-contracts'), 'contracts.index', ['contracts.*'], 'contracts') : null,
            $isDeliverResponsible && $can('checklists.deliver') ? self::item('nav.my-team.hand-out', __('general.pending-delivers'), 'checklist-items.pending-delivers', ['checklist-items.pending-delivers'], 'items', 'nav.checklists', 'checklist-items.pending-delivers-counter') : null,
            $isDeliverResponsible && $can('checklists.receive') ? self::item('nav.my-team.take-back', __('general.pending_returns'), 'checklist-items.pending-returns', ['checklist-items.pending-returns'], 'items', null, 'checklist-items.pending-returns-counter') : null,
        ]));

        $settings = array_values(array_filter([
            $editsBasics && $can('project-basics.view') ? $basics : null,
            $can('access-management.view') ? self::item('nav.settings.access', __('access.title'), 'access-management.index', ['access-management.*'], 'cog', 'nav.access') : null,
            $can('activity-log.view') ? self::item('nav.settings.activity', __('general.activity-log'), 'activity-log.index', ['activity-log.*'], 'activity-log', 'nav.activity-log') : null,
        ]));

        // Organisation (reference pages, C0-Q4) comes last: C0 put it under My
        // space, but there its long list pushed a manager's My team below the
        // fold. Frequent work first is C1's objective (c1-implementation.md §2).
        $areas = array_values(array_filter([
            ['id' => 'my-space', 'label' => __('general.nav.area.my-space'), 'items' => $mySpace],
            $myTeam !== [] ? ['id' => 'my-team', 'label' => __('general.nav.area.my-team'), 'items' => $myTeam] : null,
            $hrOps !== [] ? ['id' => 'hr', 'label' => __('general.nav.area.hr'), 'items' => $hrOps] : null,
            $settings !== [] ? ['id' => 'settings', 'label' => __('general.nav.area.settings'), 'items' => $settings] : null,
            $organisation !== [] ? ['id' => 'organisation', 'label' => __('general.nav.organisation'), 'items' => $organisation] : null,
        ]));

        return ['inbox' => self::inbox($user, $can), 'areas' => $areas];
    }

    /**
     * The area label for a route, as the active role's menu places it: the
     * first crumb of the page. Null when the route is not in the menu.
     */
    public static function areaFor(?string $routeName, User $user, bool $isDeliverResponsible = false): ?string
    {
        if ($routeName === null) {
            return null;
        }

        $nav = self::for($user, $isDeliverResponsible);
        $matches = fn (array $item): bool => collect($item['active'] ?? [])->contains(fn ($pattern) => \Illuminate\Support\Str::is($pattern, $routeName));
        $contains = function (array $items) use (&$contains, $matches): bool {
            foreach ($items as $item) {
                if (isset($item['children']) ? $contains($item['children']) : $matches($item)) {
                    return true;
                }
            }

            return false;
        };

        foreach ($nav['areas'] as $area) {
            if ($contains($area['items'])) {
                return $area['label'];
            }
        }

        return null;
    }

    /**
     * Staff of IT, CT and HR hand out and take back checklist items (the rule
     * the sidebar has always used).
     */
    public static function isDeliverResponsible(User $user): bool
    {
        $department = $user->profile?->contracts()->with('position.department')->first()?->position?->department?->code;

        return in_array($department, ['IT', 'CT', 'HR'], true);
    }

    /**
     * Interim work inbox (C0-Q2) until C3 ships its page: leave requests
     * waiting on this role, from the same scope the leave list uses. Pending
     * requests for roles that decide; decided ones not yet seen by HR for
     * roles that review. Null when the role has neither source.
     *
     * @return array{count: int, route: string}|null
     */
    private static function inbox(User $user, callable $can): ?array
    {
        $decides = $can('leaves.decide');
        $reviews = $can('leaves.review');

        if (! $decides && ! $reviews) {
            return null;
        }

        try {
            $count = LeaveRequest::query()
                ->manageableBy($user)
                ->where(function ($query) use ($decides, $reviews) {
                    if ($decides) {
                        $query->orWhere('status', LeaveRequestStatus::Pending);
                    }
                    if ($reviews) {
                        $query->orWhere(fn ($q) => $q->where('status', '!=', LeaveRequestStatus::Pending)->where('hr_approved', false));
                    }
                })
                ->count();
        } catch (Throwable $e) {
            // A badge must not take every page down with it.
            report($e);
            $count = 0;
        }

        return ['count' => $count, 'route' => 'leaves.all'];
    }

    /** @return array<string, mixed> */
    private static function item(string $id, string $label, string $route, array $active, ?string $icon = null, ?string $tour = null, ?string $counter = null): array
    {
        return compact('id', 'label', 'route', 'active', 'icon', 'tour', 'counter');
    }

    /** @return array<string, mixed> */
    private static function group(string $id, string $label, string $icon, array $children, ?string $tour = null): array
    {
        return compact('id', 'label', 'icon', 'children', 'tour');
    }
}
