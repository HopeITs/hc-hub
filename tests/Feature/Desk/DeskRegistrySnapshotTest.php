<?php

use App\Models\Permission;
use App\Support\Desk\Destinations;
use App\Support\Desk\PageCards;
use App\Support\Desk\Panels;
use App\Support\Desk\Shortcuts;
use Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Core\Models\User;

/*
| The sidebar and the Desk as every kind of viewer gets them: each role of
| config/access-control.php, an administrator, the HR app's roles, an HR
| officer holding the HR permissions, and people with no role (with and
| without an employee record). Modules add their pages and cards through the
| module registry; this snapshot proves the registry changes nothing a viewer
| sees. Rewrite it on purpose with DESK_SNAPSHOT_UPDATE=1.
*/

/**
 * @return array<string, mixed>
 */
function deskSnapshotFor(User $user): array
{
    $destinations = app(Destinations::class);
    $panels = app(Panels::class);

    return [
        'sidebar' => $destinations->sidebarFor($user),
        'destinations' => $destinations->for($user),
        'allows' => array_values(array_filter($destinations->routes(), fn (string $route): bool => $destinations->allows($user, $route))),
        'panels' => [
            'available' => $panels->availableTo($user),
            'locked' => $panels->lockedFor($user),
            'default' => $panels->defaultLayoutFor($user),
            'starting' => $panels->startingLayoutFor($user),
            'layout' => $panels->layoutFor($user),
            'items_for_routes' => $panels->itemsForRoutes($user),
            'ready' => $panels->readyLayoutsFor($user),
        ],
        'shortcuts' => app(Shortcuts::class)->for($user),
    ];
}

/**
 * @param  list<string>  $names
 * @return list<Permission>
 */
function deskSnapshotPermissions(array $names): array
{
    return array_map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'), $names);
}

test('the sidebar and the Desk match the recorded snapshot for every kind of viewer', function () {
    $this->travelTo('2026-11-16 09:00:00');
    app()->setLocale('en');
    $this->seed(RolesAndPermissionsSeeder::class);

    $hrOfficer = User::factory()->role('HR-SYR')->create();
    $hrOfficer->givePermissionTo(deskSnapshotPermissions(['employees.view', 'contracts.view', 'positions.view', 'badges.view', 'statistics.view', 'leaves.view-all', 'leaves.view-own', 'project-basics.view', 'recruitment-requests.view', 'recruitment-vacancies.view', 'checklists.view-all', 'archives.view', 'news.view', 'activity-log.view']));

    $employee = User::factory()->role('employee')->create();
    $employee->givePermissionTo(deskSnapshotPermissions(['leaves.view-own', 'news.view', 'checklists.view-own', 'archives.view']));

    $viewers = [];

    foreach (array_keys(config('access-control.role_baselines')) as $role) {
        $viewers[$role] = User::factory()->role($role)->create();
    }

    $viewers += [
        'Super Admin' => User::factory()->role('Super Admin')->create(),
        'HR-SYR (HR permissions)' => $hrOfficer,
        'HR-ALP' => User::factory()->role('HR-ALP')->create(),
        'general-manager' => User::factory()->role('general-manager')->create(),
        'manager' => User::factory()->role('manager')->create(),
        'employee (own HR permissions)' => $employee,
        'no role' => User::factory()->create(),
        'no role, no employee' => User::factory()->withoutEmployee()->create(),
    ];

    $destinations = app(Destinations::class);
    $panels = app(Panels::class);
    $everyItem = array_keys($panels->availableTo($viewers['Super Admin']));

    $snapshot = [
        'routes' => array_map(fn (string $route): ?array => $destinations->find($route), $destinations->routes()),
        'page_cards' => app(PageCards::class)->routes(),
        'items' => array_combine($everyItem, array_map(fn (string $id): array => [
            'kind' => $panels->readyKindOf($id),
            'rows' => $panels->rowsOf($id),
            'min_rows' => $panels->minRowsOf($id),
            'icon' => $panels->iconOf($id),
        ], $everyItem)),
        'viewers' => array_map(deskSnapshotFor(...), $viewers),
    ];

    $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    $path = __DIR__.'/fixtures/desk-registry-snapshot.json';

    if (! is_file($path) || env('DESK_SNAPSHOT_UPDATE')) {
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $json);
        $this->markTestSkipped('Snapshot written to '.$path);
    }

    expect($json)->toBe(file_get_contents($path));
});
