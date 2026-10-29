<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\AccessPermissionCatalog;
use App\Support\HelpTopics;
use Illuminate\Support\Facades\File;

/*
 * Hub task 0.3: every permission name carries its module prefix (`hr.*` for
 * HR, `hub.*` for platform administration; tracking and media add their own).
 * A name in code that is not in the catalogue would throw or silently deny.
 */

const PERMISSION_MODULE_PREFIXES = ['hr', 'hub', 'tracking', 'media'];

/**
 * Every permission name written as a literal argument of a permission check,
 * a route middleware or a Blade directive in app/, resources/views and routes/.
 *
 * @return array<string, list<string>> name => files using it
 */
function permissionNamesUsedInSource(): array
{
    $calls = '(?:hasActivePermission|authorizeActivePermission|authorizePermission|\$can|@activepermission|givePermissionTo)';
    $patterns = [
        "/{$calls}\(\s*'([^']+)'/",
        '/active_permission:([A-Za-z0-9_.|\-]+)/',
        "/canAccessEmployee\([^;]*?'([^']+)'\)/",
    ];

    $used = [];
    $files = collect([app_path(), resource_path('views'), base_path('routes')])
        ->flatMap(fn (string $dir) => File::allFiles($dir))
        ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.php'));

    foreach ($files as $file) {
        $source = File::get($file->getPathname());

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $source, $matches);

            foreach ($matches[1] as $match) {
                foreach (explode('|', $match) as $name) {
                    $used[$name][] = $file->getRelativePathname();
                }
            }
        }
    }

    return $used;
}

test('every catalogue permission is prefixed by its module', function () {
    $names = AccessPermissionCatalog::names();

    expect($names)->not->toBeEmpty();

    foreach ($names as $name) {
        expect(explode('.', $name)[0])->toBeIn(PERMISSION_MODULE_PREFIXES, "{$name} has no module prefix");
    }
});

test('the platform groups use hub and every other existing group uses hr', function () {
    $prefixes = collect(AccessPermissionCatalog::groups())
        ->keys()
        ->mapWithKeys(fn (string $group) => [$group => explode('.', $group)[0]]);

    expect($prefixes->filter(fn (string $prefix) => $prefix === 'hub')->keys()->sort()->values()->all())
        ->toBe(['hub.access-management', 'hub.activity-log']);
});

test('every permission named in app, views and routes exists in the catalogue', function () {
    $catalogue = AccessPermissionCatalog::names();
    $used = permissionNamesUsedInSource();

    expect($used)->not->toBeEmpty();

    $unknown = collect($used)
        ->reject(fn (array $files, string $name) => in_array($name, $catalogue, true))
        ->map(fn (array $files) => array_values(array_unique($files)))
        ->all();

    expect($unknown)->toBe([]);
});

test('every permission named in config exists in the catalogue', function () {
    $catalogue = AccessPermissionCatalog::names();

    $configured = collect(config('access-control.livewire'))
        ->flatMap(fn (array $methods) => array_values($methods))
        ->merge(collect(config('access-control.role_baselines'))->flatten())
        ->merge(collect(HelpTopics::TOPICS)->pluck('permission')->filter())
        ->merge(collect(config('access-control.reference_pages_hidden_from'))
            ->keys()
            ->map(fn (string $page) => "hr.{$page}.view"))
        ->unique()
        ->values();

    expect($configured->diff($catalogue)->values()->all())->toBe([]);
});

test('no unprefixed permission name is left in a permission check', function () {
    $migration = require database_path('migrations/2026_10_29_000002_prefix_permission_names_by_module.php');
    $oldNames = array_keys($migration::renames());

    expect(array_intersect(array_keys(permissionNamesUsedInSource()), $oldNames))->toBe([]);
});

test('a new HR role receives only hr permissions, even when other modules have catalogue permissions', function () {
    config(['access-control.groups' => AccessPermissionCatalog::groups() + ['tracking.x' => ['view'], 'media.x' => ['view']]]);
    Permission::findOrCreate('tracking.x.view', 'web');
    Permission::findOrCreate('media.x.view', 'web');

    $granted = Role::findOrCreate('HR-QA', 'web')->permissions()->pluck('name');
    $hrNames = collect(AccessPermissionCatalog::names())->filter(fn (string $name) => str_starts_with($name, 'hr.'));

    expect($granted->reject(fn (string $name) => str_starts_with($name, 'hr.'))->values()->all())->toBe([])
        ->and($granted->sort()->values()->all())->toBe($hrNames->sort()->values()->all());
});
