<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Support\Desk\Cards\HrCard;
use Modules\Hr\Support\Desk\Cards\NoLeavesCard;
use Modules\Hr\Support\Desk\HrPanels;
use Spatie\Permission\Models\Role;

/*
| HrCard::link() and WorkInbox::link() return '' for a route that does not
| exist, so a renamed route leaves a card without a link and nothing fails.
| These tests are the loud check: every route name the HR Desk and the HR
| inbox point at must be registered.
*/

test('every HR card opens a registered page', function () {
    $missing = collect(app(HrPanels::class)->cards())
        ->map(fn (HrCard $card): ?string => $card->register())
        ->filter()
        ->reject(fn (string $route): bool => Route::has($route))
        ->all();

    expect($missing)->toBe([]);
});

test('every HR page that stands for a card is a registered route', function () {
    $missing = array_values(array_filter(
        array_keys(HrPanels::RoutePanels),
        fn (string $route): bool => ! Route::has($route),
    ));

    expect($missing)->toBe([]);
});

test('every route an HR card or the HR inbox links to is registered', function () {
    $files = [...File::allFiles(base_path('modules/Hr/Support/Desk')), ...File::allFiles(base_path('modules/Hr/Support/Inbox'))];

    $routes = collect($files)
        ->flatMap(function (SplFileInfo $file): array {
            preg_match_all("/->link\\('([a-z0-9.\\-]+)'/", (string) file_get_contents($file->getPathname()), $matches);

            return $matches[1];
        })
        ->unique()
        ->values();

    expect($routes)->not->toBeEmpty()
        ->and($routes->reject(fn (string $route): bool => Route::has($route))->values()->all())->toBe([]);
});

test('the no-leaves card opens a profile by username, never by id, and shows plain text without one', function () {
    $withUsername = Employee::factory()->create(['username' => 'lina.haddad', 'is_active' => true]);
    $withoutUsername = Employee::factory()->create(['username' => null, 'is_active' => true]);

    $annual = LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);

    foreach ([$withUsername, $withoutUsername] as $employee) {
        LeaveBalance::factory()->for(Contract::factory()->for($employee)->create(['is_active' => true]))->create(['type_id' => $annual->id]);
    }

    $viewer = User::factory()->create();
    Role::findOrCreate('general-manager', 'web');
    $viewer->assignRole('general-manager');

    $rows = (new NoLeavesCard)->rows($viewer)->keyBy('key');

    expect($rows['nl-'.$withUsername->id]['href'])->toBe(route('hr.employees.show', ['employee' => 'lina.haddad']))
        ->and($rows['nl-'.$withoutUsername->id]['href'])->toBe('');
});
