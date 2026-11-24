<?php

use App\Support\Desk\Destinations;
use App\Support\Desk\PageCards;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Modules\Core\Models\User;
use Modules\Tracking\Models\DonorReport;

/**
 * The pages a user's sidebar lists, in order.
 *
 * @return list<string>
 */
function deskPageRoutes(User $user): array
{
    return array_column(app(Destinations::class)->for($user), 'route');
}

test('every page renders the sidebar and the top bar with its tools', function () {
    $this->actingAs(User::factory()->role('Team Member')->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-side-nav', false)
        ->assertSee('data-desk-top-bar', false)
        ->assertSee('data-desk-ask', false)
        ->assertSee('data-panels-trigger', false)
        ->assertSee('data-notification-bell', false)
        ->assertSee('data-user-menu', false);
});

test('the panels button sits on My desk only', function () {
    $this->actingAs(User::factory()->role('General Manager')->create())
        ->get(route('projects.index'))
        ->assertOk()
        ->assertDontSee('data-panels-trigger', false);
});

test('the profile menu links to the viewer\'s own notification preferences and leaves the rules to the bell', function () {
    $html = $this->actingAs(User::factory()->role('Super Admin')->create())->get(route('dashboard'))->assertOk()->getContent();
    $xpath = new DOMXPath(tap(new DOMDocument, fn (DOMDocument $document) => $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING)));

    $menu = $xpath->query('//*[@data-user-menu]//*[@role="menu"]')->item(0);
    $links = array_map(fn (DOMElement $link): string => $link->getAttribute('href'), iterator_to_array($xpath->query('.//a[@role="menuitem"]', $menu)));
    $item = $xpath->query('.//a[@data-test="notification-settings-menu-item"]', $menu)->item(0);

    expect($links)->toBe([route('security.edit'), route('notification-settings.edit')])
        ->and(trim($item->textContent))->toBe(__('desk.my_notifications'));
});

test('the profile menu hides notification preferences while two-factor enrolment is pending', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    config(['access-control.two_factor' => ['enforced' => true, 'required_roles' => ['Super Admin']]]);

    $this->actingAs(User::factory()->role('Super Admin')->create(['email' => '']))
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('data-test="security-menu-item"', false)
        ->assertDontSee('data-test="notification-settings-menu-item"', false);
});

test('a Super Admin sidebar lists every page in groups named by the work, the Desk first and their own work second', function () {
    $groups = app(Destinations::class)->sidebarFor(User::factory()->role('Super Admin')->create());
    $routesOf = fn (string $group): array => array_column(collect($groups)->firstWhere('group', $group)['items'], 'route');

    expect(array_column($groups, 'group'))->toBe(['overview', 'workspace', 'strategy_planning', 'projects_delivery', 'monitoring_evaluation', 'funding_finance', 'people', 'recruitment', 'administration', 'you'])
        ->and($groups[0]['items'][0]['route'])->toBe('dashboard')
        ->and($routesOf('administration'))->toContain('admin.users.index', 'activity.index', 'admin.cities.index')
        ->and($routesOf('monitoring_evaluation'))->toContain('report-templates.index');
});

test('HR pages sit in groups named by the work, leave management with the people, and no hr_ group remains', function () {
    $groups = app(Destinations::class)->sidebarFor(User::factory()->role('Super Admin')->create());
    $routesOf = fn (string $group): array => array_column(collect($groups)->firstWhere('group', $group)['items'], 'route');

    expect($routesOf('people'))->toBe(['hr.employees.index', 'hr.contracts.index', 'hr.positions.index', 'hr.positions.team', 'hr.badges.index', 'hr.leave.team', 'hr.leave.balances', 'hr.leave.holidays', 'hr.statistics.general', 'hr.reports.index'])
        ->and($routesOf('recruitment'))->toContain('hr.recruitment.requests.index', 'hr.recruitment.campaigns.index', 'hr.recruitment.applicants.index')
        ->and($routesOf('workspace'))->toContain('tasks.index', 'calendar.index', 'hr.checklists.index', 'hr.news.index')
        ->and($routesOf('workspace'))->not->toContain('hr.leave.team-calendar')
        ->and($routesOf('administration'))->toContain('activity.index', 'hr.settings.index')
        ->and(array_filter(Destinations::Groups, fn (string $group): bool => str_starts_with($group, 'hr_')))->toBe([]);
});

test('the people and recruitment groups carry the hub\'s own headings', function () {
    $groups = collect(app(Destinations::class)->sidebarFor(User::factory()->role('Super Admin')->create()))->pluck('label', 'group');

    expect($groups['people'])->toBe(__('navigation.group_people'))->not->toBe('navigation.group_people')
        ->and($groups['recruitment'])->toBe(__('navigation.group_recruitment'))->not->toBe('navigation.group_recruitment');
});

test('the notifications inbox belongs to the overview and stays out of the sidebar', function () {
    $user = User::factory()->role('Super Admin')->create();
    $destinations = app(Destinations::class);
    $links = collect($destinations->sidebarFor($user))->flatMap(fn (array $group): array => array_column($group['items'], 'route'));

    expect($destinations->find('notifications.index')['group'])->toBe('overview')
        ->and($links)->not->toContain('notifications.index');
});

test('no page a viewer may open falls out of the sidebar, whatever their role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $destinations = app(Destinations::class);
    $reachedElsewhere = ['notifications.index', 'notification-settings.edit', 'admin.notification-settings.edit', 'hr.statistics.contracts', 'hr.statistics.salaries', 'hr.activity.index', 'hr.attendance.upload', 'hr.leave.team-calendar', 'hr.reports.leaving-reasons', 'hr.reports.recruitment-funnel', 'hr.reports.leave', 'hr.reports.vacancies', 'hr.reports.training', 'hr.reports.profile-completeness'];

    foreach ($destinations->routes() as $route) {
        expect(Destinations::Groups)->toContain($destinations->find($route)['group']);
    }

    foreach ([...array_keys(config('access-control.role_baselines')), 'Super Admin', 'HR-SYR', 'manager', 'employee'] as $role) {
        $user = User::factory()->role($role)->create();
        $expected = array_values(array_diff(deskPageRoutes($user), $reachedElsewhere));
        $linked = collect($destinations->sidebarFor($user))->flatMap(fn (array $group): array => array_column($group['items'], 'route'))->all();

        sort($expected);
        sort($linked);

        expect($linked)->toBe($expected, $role);
    }
});

test('the team calendar has no sidebar link of its own, stays a destination and lights the Calendar link', function () {
    $user = User::factory()->role('Super Admin')->create();
    $destinations = app(Destinations::class);

    expect($destinations->allows($user, 'hr.leave.team-calendar'))->toBeTrue()
        ->and($destinations->currentFor($user, 'hr.leave.team-calendar')['route'])->toBe('calendar.index')
        ->and($destinations->find('calendar.index')['label'])->toBe(__('common.calendar'));
});

test('the three HR statistics pages share one sidebar link and stay open to the Ask bar', function () {
    $user = User::factory()->role('Super Admin')->create();
    $destinations = app(Destinations::class);
    $links = collect($destinations->sidebarFor($user))->flatMap(fn (array $group): array => array_column($group['items'], 'route'));

    expect($links)->toContain('hr.statistics.general')
        ->not->toContain('hr.statistics.contracts', 'hr.statistics.salaries')
        ->and(deskPageRoutes($user))->toContain('hr.statistics.contracts', 'hr.statistics.salaries')
        ->and($destinations->currentFor($user, 'hr.statistics.salaries'))->toMatchArray(['route' => 'hr.statistics.general', 'group' => 'people', 'group_label' => __('navigation.group_people')]);
});

test('the old HR activity log is opened from the Activity log, which keeps the one sidebar link', function () {
    $user = User::factory()->role('Super Admin')->create();
    $destinations = app(Destinations::class);
    $links = collect($destinations->sidebarFor($user))->flatMap(fn (array $group): array => array_column($group['items'], 'route'));

    expect($links)->toContain('activity.index')->not->toContain('hr.activity.index')
        ->and($destinations->currentFor($user, 'hr.activity.index'))->toMatchArray(['route' => 'activity.index', 'group' => 'administration']);

    $this->actingAs($user)->get(route('activity.index'))->assertOk()->assertSee(route('hr.activity.index'));
    $this->actingAs($user)->get(route('hr.activity.index'))->assertOk()->assertSee('data-activity-current-log', false);
});

test('an HR page about the viewer themself is not offered to an administrator with no employee record', function () {
    $routes = deskPageRoutes(User::factory()->role('Super Admin')->create(['employee_id' => null]));

    expect($routes)->not->toContain('hr.employees.me', 'hr.leave.index', 'hr.archives.mine');
});

test('a Team Member sidebar leaves out the pages their role cannot open', function () {
    $routes = deskPageRoutes(User::factory()->role('Team Member')->create());

    expect($routes)->toContain('dashboard', 'security.edit', 'notification-settings.edit')
        ->not->toContain('admin.users.index', 'admin.accounts.index', 'transfers.index');
});

test('the rendered sidebar links only what the role may open', function () {
    $this->actingAs(User::factory()->role('Team Member')->create())
        ->get(route('dashboard'))
        ->assertSee('data-side-link="dashboard"', false)
        ->assertDontSee('data-side-link="admin.users.index"', false)
        ->assertDontSee('data-side-link="admin.accounts.index"', false);
});

test('the donor report builder goes only to reports.aggregate holders, donor reports also to their responsibles', function () {
    $employeeId = insertHrEmployee();
    $responsible = User::factory()->role('Team Member')->create(['employee_id' => $employeeId]);
    DonorReport::factory()->create(['responsible_employee_id' => $employeeId]);

    expect(deskPageRoutes(User::factory()->role('Team Member')->create()))->not->toContain('donor-reports.compose')->not->toContain('donor-reports.index')
        ->and(deskPageRoutes($responsible))->toContain('donor-reports.index')->not->toContain('donor-reports.compose')
        ->and(deskPageRoutes(User::factory()->role('Financial')->create()))->toContain('donor-reports.index')->not->toContain('donor-reports.compose')
        ->and(deskPageRoutes(User::factory()->role('MEAL')->create()))->toContain('donor-reports.compose', 'donor-reports.index')
        ->and(deskPageRoutes(User::factory()->role('Financial')->create()))->not->toContain('reporting-duties.index');
});

test('the old My Reporting Duties address leads to Donor Reports', function () {
    $this->actingAs(User::factory()->role('Team Member')->create())
        ->get('/reporting-duties')
        ->assertRedirect(route('donor-reports.index'));
});

test('the donor reports register sits under monitoring and evaluation, labelled Donor reports, the builder labelled Donor report builder', function () {
    $groups = collect(app(Destinations::class)->sidebarFor(User::factory()->role('Super Admin')->create()))->keyBy('group');
    $items = collect($groups->get('monitoring_evaluation')['items'])->keyBy('route');

    expect($items->get('donor-reports.index')['label'])->toBe('Donor reports')
        ->and($items->get('donor-reports.compose')['label'])->toBe('Donor report builder');
});

test('funding pages follow the module permissions of each role', function () {
    $funding = ['donors.index', 'proposals.index', 'funding-agreements.index', 'transfers.index'];

    expect(deskPageRoutes(User::factory()->role('Financial')->create()))->toContain(...$funding)
        ->and(deskPageRoutes(User::factory()->role('Financial')->create()))->toContain('admin.accounts.index')
        ->and(deskPageRoutes(User::factory()->role('Public Relations Manager')->create()))->toContain('donors.index', 'proposals.index', 'funding-agreements.index')->not->toContain('transfers.index')
        ->and(array_intersect(deskPageRoutes(User::factory()->role('Team Member')->create()), $funding))->toBe([]);
});

test('every destination is a registered route', function () {
    foreach (deskPageRoutes(User::factory()->role('Super Admin')->create()) as $route) {
        expect(Route::has($route))->toBeTrue($route);
    }
});

test('the top bar links to the user guideline and the bug report form, both in a new tab', function () {
    $html = $this->actingAs(User::factory()->role('Team Member')->create())->get(route('dashboard'))->assertOk()->getContent();
    $xpath = new DOMXPath(tap(new DOMDocument, fn (DOMDocument $document) => $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING)));

    $guideline = $xpath->query('//*[@data-desk-top-bar]//a[@data-guideline]');
    $report = $xpath->query('//a[@data-report-bug]');

    expect($guideline)->toHaveCount(1)
        ->and($guideline->item(0)->getAttribute('target'))->toBe('_blank')
        ->and($report)->toHaveCount(1)
        ->and($report->item(0)->getAttribute('href'))->toBe('https://forms.gle/5QfGGarkeejAe3HS6')
        ->and($report->item(0)->getAttribute('target'))->toBe('_blank')
        ->and($report->item(0)->getAttribute('rel'))->toContain('noopener');
});

test('the logo link keeps a single accessible name while the light and dark logos swap', function () {
    $html = $this->actingAs(User::factory()->role('Team Member')->create())->get(route('dashboard'))->assertOk()->getContent();
    $xpath = new DOMXPath(tap(new DOMDocument, fn (DOMDocument $document) => $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING)));
    $logo = $xpath->query('//a[@data-desk-logo]');

    expect($logo)->toHaveCount(1)
        ->and($logo->item(0)->getAttribute('aria-label'))->toBe(__('desk.logo_label'))
        ->and($logo->item(0)->getAttribute('href'))->toBe(route('dashboard'));
});

test('the current page is the sidebar link it lights up, a register lighting up for its own sub-pages', function () {
    $user = User::factory()->role('Super Admin')->create();
    $destinations = app(Destinations::class);

    expect($destinations->currentFor($user, 'dashboard'))->toMatchArray(['route' => 'dashboard', 'group' => 'overview'])
        ->and($destinations->currentFor($user, 'projects.show'))->toMatchArray(['route' => 'projects.index', 'group' => 'projects_delivery', 'group_label' => __('navigation.group_projects_delivery')])
        ->and($destinations->currentFor($user, 'reports.monthly-matrix')['route'])->toBe('reports.monthly-matrix')
        ->and($destinations->currentFor($user, 'notifications.index'))->toBeNull();
});

test('the top bar says where you are: the sidebar group, then the current page', function () {
    $html = $this->actingAs(User::factory()->role('Super Admin')->create())->get(route('projects.index'))->assertOk()->getContent();
    $xpath = new DOMXPath(tap(new DOMDocument, fn (DOMDocument $document) => $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING)));

    $where = $xpath->query('//*[@data-desk-top-bar]//nav[@data-top-where]')->item(0);
    $page = $xpath->query('.//a', $where)->item(0);

    expect(trim($xpath->query('.//li[1]', $where)->item(0)->textContent))->toBe(__('navigation.group_projects_delivery'))
        ->and(trim($page->textContent))->toBe(__('common.projects'))
        ->and($page->getAttribute('href'))->toBe(route('projects.index'))
        ->and($page->getAttribute('aria-current'))->toBe('page');
});

test('the help menu holds the tour, the guideline and the bug report behind one button', function () {
    $html = $this->actingAs(User::factory()->role('Team Member')->create())->get(route('dashboard'))->assertOk()->getContent();
    $xpath = new DOMXPath(tap(new DOMDocument, fn (DOMDocument $document) => $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING)));

    $menu = $xpath->query('//*[@data-desk-top-bar]//*[@data-help-menu]')->item(0);

    expect($xpath->query('.//button[@data-help-trigger]', $menu)->item(0)->getAttribute('aria-haspopup'))->toBe('menu')
        ->and($xpath->query('.//*[@role="menu"]//*[@data-test="tour-start-button"]', $menu))->toHaveCount(1)
        ->and($xpath->query('.//*[@role="menu"]//a[@data-guideline]', $menu))->toHaveCount(1)
        ->and($xpath->query('.//*[@role="menu"]//a[@data-report-bug]', $menu))->toHaveCount(1);
});

test('the risk register is no longer in the sidebar, and the risk library is only for the MEAL Manager slot holder', function () {
    $general = User::factory()->role('General Manager')->create();

    expect(deskPageRoutes($general))->not->toContain('risks.index')
        ->and(deskPageRoutes($general))->not->toContain('admin.risk-library.index')
        ->and(deskPageRoutes(mealManager()))->toContain('admin.risk-library.index');
});

test('a super admin outside the MEAL Manager slot is not offered the risk library its route refuses', function () {
    $admin = User::factory()->role('Super Admin')->create();

    $this->actingAs($admin)->get(route('admin.risk-library.index'))->assertForbidden();

    expect(deskPageRoutes($admin))->not->toContain('admin.risk-library.index')
        ->and(app(PageCards::class)->allows($admin, 'admin.risk-library.index'))->toBeFalse()
        ->and(deskPageRoutes($admin))->toContain('admin.users.index');
});

test('the sidebar folds each group into one row and opens the group of the current page', function () {
    $this->actingAs(User::factory()->role('Super Admin')->create())
        ->get(route('projects.index'))
        ->assertSee('data-side-group-toggle="projects_delivery"', false)
        ->assertSee('data-side-group-toggle="administration"', false)
        ->assertDontSee('data-side-group-toggle="overview"', false)
        ->assertSee('x-data="{ open: true ||', false)
        ->assertSee('x-data="{ open: false ||', false)
        ->assertSee('data-side-link="projects.index"', false)
        ->assertSee('data-side-fly="projects_delivery"', false);
});

test('every wire:navigate link prefetches on hover unless it already names a modifier', function () {
    $html = Blade::render('<a href="/a" wire:navigate>A</a><a href="/b" wire:navigate.preserve-scroll>B</a>');

    expect($html)->toContain('href="/a" wire:navigate.hover>')
        ->toContain('href="/b" wire:navigate.preserve-scroll>')
        ->not->toContain('wire:navigate.preserve-scroll.hover');
});

test('the Checklists page is named Equipment, العهد in Arabic, at the address it always had', function (string $locale, string $name) {
    app()->setLocale($locale);

    expect(app(Destinations::class)->find('hr.checklists.index')['label'])->toBe($name)
        ->and(__('hr_checklists.titles.index'))->toBe($name)
        ->and(route('hr.checklists.index', absolute: false))->toBe('/hr/checklists');
})->with([
    'English' => ['en', 'Equipment'],
    'Arabic' => ['ar', 'العهد'],
]);
