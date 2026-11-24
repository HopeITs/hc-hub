<?php

use App\Models\Role;
use Database\Seeders\EntitlementLibrarySeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeEntitlement;
use Modules\Hr\Models\Entitlement;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\PositionEntitlement;
use Modules\Hr\Support\Entitlements\Entitlements;
use Modules\Hr\Support\Journeys\OffboardingJourneys;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| The Permissions Library (meeting 19/11, track 5): professional access outside
| the system, recorded per person from their position's defaults, kept by HR of
| their city and their direct manager, and never a Spatie permission.
| "Today" is Thursday 2026-11-19.
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-11-19 10:00:00');
    Notification::fake();

    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $this->city = City::factory()->create();
    $this->department = Department::factory()->create();
    $this->officerLevel = JobLevel::factory()->create(['grade' => 5]);
    $this->managerLevel = JobLevel::factory()->create(['grade' => 3]);
    $this->contractFields = ['contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true];

    $this->managerPosition = Position::factory()->create(['city_id' => $this->city->id, 'job_level_id' => $this->managerLevel->id]);
    $this->position = Position::factory()->create(['city_id' => $this->city->id, 'job_level_id' => $this->officerLevel->id, 'direct_manager_id' => $this->managerPosition->id]);
    $this->otherPosition = Position::factory()->create(['city_id' => $this->city->id, 'job_level_id' => $this->managerLevel->id, 'direct_manager_id' => $this->managerPosition->id]);

    $this->userWith = function (string $role, array $permissions, ?int $employeeId = null): User {
        $user = User::factory()->role($role)->create($employeeId === null ? [] : ['employee_id' => $employeeId]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user->fresh();
    };

    $this->manager = ($this->userWith)('manager', ['entitlements.assign']);
    Contract::factory()->create(['employee_id' => $this->manager->employee_id, 'position_id' => $this->managerPosition->id] + $this->contractFields);

    $this->hr = ($this->userWith)('HR-'.strtoupper($this->city->slug), ['employees.update', 'employees.view', 'entitlements.assign', 'entitlements.manage-library']);

    $this->entitlement = function (string $name, string $category = 'other', ?Position $position = null, ?JobLevel $jobLevel = null): Entitlement {
        $entitlement = Entitlement::factory()->create(['name_en' => $name, 'name_ar' => $name, 'category' => $category]);

        if ($position !== null) {
            PositionEntitlement::query()->create(['entitlement_id' => $entitlement->id, 'position_id' => $position->id]);
        }

        if ($jobLevel !== null) {
            PositionEntitlement::query()->create(['entitlement_id' => $entitlement->id, 'job_level_id' => $jobLevel->id]);
        }

        return $entitlement;
    };

    $this->contract = fn (array $attributes = []): Contract => Contract::factory()->create($attributes + [
        'employee_id' => Employee::factory()->create(['city_id' => $this->city->id])->id,
        'position_id' => $this->position->id,
    ] + $this->contractFields)->fresh();

    $this->rowsOf = fn (int $employeeId) => EmployeeEntitlement::query()->where('employee_id', $employeeId)->get()->keyBy('entitlement_id');
    $this->panel = fn (User $user, Employee $employee) => Livewire::actingAs($user)->test('hr::employees.entitlements', ['employeeId' => $employee->id]);
    $this->library = fn (User $user) => Livewire::actingAs($user)->test('hr::entitlements.index');
});

// ── Defaults from a contract ─────────────────────────────────────────

test('a new contract records the position and job level defaults as required', function () {
    $database = ($this->entitlement)('IT database', 'database', $this->position);
    $cameras = ($this->entitlement)('Cameras', 'camera', jobLevel: $this->officerLevel);
    ($this->entitlement)('Old group', 'telegram', $this->position)->update(['is_active' => false]);
    ($this->entitlement)('Elsewhere', 'other', $this->otherPosition);

    $contract = ($this->contract)();
    $rows = EmployeeEntitlement::query()->where('employee_id', $contract->employee_id)->get();

    expect($rows->pluck('entitlement_id')->sort()->values()->all())->toBe(collect([$database->id, $cameras->id])->sort()->values()->all())
        ->and($rows->every(fn (EmployeeEntitlement $row): bool => $row->state === EmployeeEntitlement::STATE_REQUIRED && $row->source === EmployeeEntitlement::SOURCE_DEFAULT))->toBeTrue();
});

test('a draft records nothing until it becomes a real contract', function () {
    $item = ($this->entitlement)('IT database', 'database', $this->position);
    $draft = ($this->contract)(['type' => ContractTypeEnum::Draft->value]);

    expect(EmployeeEntitlement::query()->count())->toBe(0);

    $draft->update(['type' => ContractTypeEnum::FullTime->value]);

    expect(EmployeeEntitlement::query()->where('employee_id', $draft->employee_id)->pluck('entitlement_id')->all())->toBe([$item->id]);
});

test('a renewal does not duplicate and keeps what was decided', function () {
    $database = ($this->entitlement)('IT database', 'database', $this->position);
    $group = ($this->entitlement)('IT group', 'telegram', $this->position);
    $first = ($this->contract)();
    $employee = Employee::query()->find($first->employee_id);

    $rows = ($this->rowsOf)($employee->id);
    app(Entitlements::class)->markGranted($rows[$database->id], $this->hr);
    app(Entitlements::class)->revoke($rows[$group->id], $this->hr);

    // The renewal is issued while the first contract still runs.
    ($this->contract)(['employee_id' => $employee->id, 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);

    $rows = ($this->rowsOf)($employee->id);

    expect($rows)->toHaveCount(2)
        ->and($rows[$database->id]->state)->toBe(EmployeeEntitlement::STATE_GRANTED)
        ->and($rows[$group->id]->state)->toBe(EmployeeEntitlement::STATE_REVOKED);
});

test('coming back after leaving makes revoked defaults required again', function () {
    ($this->entitlement)('IT group', 'telegram', $this->position);
    $first = ($this->contract)(['start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
    $row = EmployeeEntitlement::query()->where('employee_id', $first->employee_id)->sole();
    app(Entitlements::class)->revoke($row, $this->hr);
    $first->update(['is_active' => false]);

    ($this->contract)(['employee_id' => $first->employee_id]);

    expect($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and(EmployeeEntitlement::query()->where('employee_id', $first->employee_id)->count())->toBe(1);
});

test('a move adds the new defaults and marks the old ones for review without revoking', function () {
    $old = ($this->entitlement)('Officer database', 'database', $this->position);
    $new = ($this->entitlement)('Other group', 'telegram', $this->otherPosition);
    $manual = ($this->entitlement)('Printer', 'it_maintenance');
    $contract = ($this->contract)();
    $employee = Employee::query()->find($contract->employee_id);
    app(Entitlements::class)->add($employee, $manual, $this->hr);

    $contract->update(['position_id' => $this->otherPosition->id]);

    $rows = ($this->rowsOf)($employee->id);

    expect($rows[$new->id]->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and($rows[$old->id]->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and($rows[$old->id]->needs_review_at)->not->toBeNull()
        ->and($rows[$manual->id]->needs_review_at)->toBeNull();

    ($this->panel)($this->hr, $employee)->call('keep', $rows[$old->id]->id);

    expect($rows[$old->id]->fresh()->needs_review_at)->toBeNull();
});

// ── The profile tab: add, mark done, revoke ──────────────────────────

test('city HR adds, marks done and revokes an item by hand', function () {
    $item = ($this->entitlement)('Cameras', 'camera');
    $employee = Employee::query()->find(($this->contract)()->employee_id);

    $panel = ($this->panel)($this->hr, $employee)
        ->assertSee(__('hr_entitlements.panel_title'))
        ->set('addId', (string) $item->id)
        ->set('note', 'Center gate only')
        ->call('add')
        ->assertHasNoErrors();

    $row = EmployeeEntitlement::query()->where('employee_id', $employee->id)->sole();

    expect([$row->source, $row->state, $row->note])->toBe([EmployeeEntitlement::SOURCE_MANUAL, EmployeeEntitlement::STATE_REQUIRED, 'Center gate only']);

    $panel->call('markGranted', $row->id);
    $row->refresh();

    expect($row->state)->toBe(EmployeeEntitlement::STATE_GRANTED)
        ->and($row->granted_by)->toBe($this->hr->id);

    $panel->call('revoke', $row->id);
    $row->refresh();

    expect($row->state)->toBe(EmployeeEntitlement::STATE_REVOKED)
        ->and($row->revoked_by)->toBe($this->hr->id);

    // Adding it again brings the same row back, never a second one.
    $panel->set('addId', (string) $item->id)->call('add');

    expect(EmployeeEntitlement::query()->where('employee_id', $employee->id)->count())->toBe(1)
        ->and($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_REQUIRED);
});

test('the direct manager acts on a direct report', function () {
    $item = ($this->entitlement)('Team group', 'telegram');
    $employee = Employee::query()->find(($this->contract)()->employee_id);

    ($this->panel)($this->manager, $employee)
        ->assertSee(__('hr_entitlements.panel_title'))
        ->set('addId', (string) $item->id)
        ->call('add')
        ->assertHasNoErrors();

    expect(EmployeeEntitlement::query()->where('employee_id', $employee->id)->count())->toBe(1);
});

test('HR of another city, a manager who is not the direct one and the person themself are refused', function () {
    $item = ($this->entitlement)('Team group', 'telegram', $this->position);
    $employee = Employee::query()->find(($this->contract)()->employee_id);
    $row = EmployeeEntitlement::query()->where('employee_id', $employee->id)->sole();

    $otherCity = City::factory()->create();
    $otherHr = ($this->userWith)('HR-'.strtoupper($otherCity->slug), ['employees.update', 'entitlements.assign']);

    $otherSeat = Position::factory()->create(['city_id' => $this->city->id]);
    $otherManager = ($this->userWith)('manager', ['entitlements.assign']);
    Contract::factory()->create(['employee_id' => $otherManager->employee_id, 'position_id' => $otherSeat->id] + $this->contractFields);

    $self = ($this->userWith)('employee', ['entitlements.assign'], $employee->id);

    foreach (['other-city HR' => $otherHr, 'not the direct manager' => $otherManager, 'the employee' => $self] as $who => $user) {
        expect(app(Entitlements::class)->canAssign($user, $employee))->toBeFalse($who);

        ($this->panel)($user, $employee)->assertDontSee(__('hr_entitlements.panel_title'));
        ($this->panel)($user, $employee)->call('markGranted', $row->id)->assertForbidden();
        ($this->panel)($user, $employee)->set('addId', (string) $item->id)->call('add')->assertForbidden();
    }

    expect($row->fresh()->state)->toBe(EmployeeEntitlement::STATE_REQUIRED);
});

test('a row of another person cannot be reached through the panel', function () {
    ($this->entitlement)('Team group', 'telegram', $this->position);
    $mine = Employee::query()->find(($this->contract)()->employee_id);
    $theirs = Employee::query()->find(($this->contract)()->employee_id);
    $theirRow = EmployeeEntitlement::query()->where('employee_id', $theirs->id)->sole();

    ($this->panel)($this->hr, $mine)->call('markGranted', $theirRow->id)->assertNotFound();

    expect($theirRow->fresh()->state)->toBe(EmployeeEntitlement::STATE_REQUIRED);
});

test('the profile shows the tab only to those who may act', function () {
    $employee = Employee::query()->find(($this->contract)()->employee_id);
    $employee->update(['username' => 'ent.person']);

    Livewire::actingAs($this->hr)->test('hr::employees.show', ['employee' => $employee->fresh()])
        ->assertSee(__('hr_employees.tabs.entitlements'));

    Livewire::actingAs(($this->userWith)('employee', [], $employee->id))->test('hr::employees.show', ['employee' => $employee->fresh()])
        ->assertDontSee(__('hr_employees.tabs.entitlements'));
});

test('no Spatie permission is created or granted by the library', function () {
    $permissions = Permission::query()->count();
    ($this->entitlement)('IT database', 'database', $this->position);
    $employee = Employee::query()->find(($this->contract)()->employee_id);
    $user = ($this->userWith)('employee', [], $employee->id);
    $before = $user->getAllPermissions()->pluck('name')->sort()->values()->all();

    app(Entitlements::class)->markGranted(EmployeeEntitlement::query()->where('employee_id', $employee->id)->sole(), $this->hr);
    app(Entitlements::class)->add($employee, ($this->entitlement)('Cameras', 'camera'), $this->hr);

    expect(Permission::query()->count())->toBe($permissions)
        ->and($user->fresh()->getAllPermissions()->pluck('name')->sort()->values()->all())->toBe($before);
});

// ── The library page ─────────────────────────────────────────────────

test('HR creates an item and links only positions of its city', function () {
    $foreign = Position::factory()->create(['city_id' => City::factory()->create()->id]);

    $page = ($this->library)($this->hr)
        ->call('newItem')
        ->set('item.name_en', 'IT database')
        ->set('item.name_ar', 'قاعدة بيانات IT')
        ->set('item.category', 'database')
        ->call('save')
        ->assertHasNoErrors();

    $item = Entitlement::query()->sole();
    expect([$item->name_en, $item->category, $item->is_active])->toBe(['IT database', 'database', true]);

    $page->call('openItem', $item->id)->call('addPosition', $this->position->id);

    expect(PositionEntitlement::query()->where(['entitlement_id' => $item->id, 'position_id' => $this->position->id])->exists())->toBeTrue();

    // Each refused call on a page of its own: a failed request leaves no state to continue from.
    $opened = fn () => ($this->library)($this->hr)->call('openItem', $item->id);

    $opened()->call('addPosition', $foreign->id)->assertNotFound();
    expect(PositionEntitlement::query()->where('position_id', $foreign->id)->exists())->toBeFalse();

    // A job level is every city's: not a city HR.
    $opened()->set('jobLevelId', (string) $this->officerLevel->id)->call('addJobLevel')->assertForbidden();
    expect(PositionEntitlement::query()->whereNotNull('job_level_id')->exists())->toBeFalse();

    $foreignLink = PositionEntitlement::query()->create(['entitlement_id' => $item->id, 'position_id' => $foreign->id]);
    $opened()->call('removeLink', $foreignLink->id)->assertForbidden();
    expect($foreignLink->fresh())->not->toBeNull();
});

test('an administrator links a job level', function () {
    $admin = ($this->userWith)('Super Admin', ['entitlements.manage-library']);
    $item = ($this->entitlement)('Cameras', 'camera');

    ($this->library)($admin)
        ->call('openItem', $item->id)
        ->set('jobLevelId', (string) $this->officerLevel->id)
        ->call('addJobLevel')
        ->assertHasNoErrors();

    expect(PositionEntitlement::query()->where(['entitlement_id' => $item->id, 'job_level_id' => $this->officerLevel->id])->exists())->toBeTrue();
});

test('an item someone held is switched off, not deleted', function () {
    $item = ($this->entitlement)('IT database', 'database', $this->position);
    ($this->contract)();
    $admin = ($this->userWith)('Super Admin', ['entitlements.manage-library']);

    ($this->library)($admin)->call('deleteItem', $item->id);
    expect($item->fresh())->not->toBeNull();

    ($this->library)($admin)->call('toggleActive', $item->id);
    expect($item->fresh()->is_active)->toBeFalse();
});

test('the library page is closed to managers and employees', function () {
    $manager = ($this->userWith)('manager', ['entitlements.manage-library']);
    $employee = ($this->userWith)('employee', []);

    ($this->library)($manager)->assertForbidden();
    ($this->library)($employee)->assertForbidden();
    $this->actingAs($employee)->get(route('hr.entitlements.index'))->assertForbidden();
});

test('the library page opens for HR', function () {
    $this->actingAs($this->hr)->get(route('hr.entitlements.index'))->assertOk()->assertSee(__('hr_entitlements.title'));
});

// ── The exit journey ─────────────────────────────────────────────────

test('the exit journey lists items still held until they are revoked', function () {
    $item = ($this->entitlement)('IT database', 'database', $this->position);
    $contract = ($this->contract)(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    $employee = Employee::query()->find($contract->employee_id);
    $row = EmployeeEntitlement::query()->where('employee_id', $employee->id)->sole();
    app(Entitlements::class)->markGranted($row, $this->hr);

    $contract->update(['terminated_at' => today()->toDateString(), 'is_active' => false]);

    $journeys = app(OffboardingJourneys::class);
    $steps = fn () => collect($journeys->steps($employee->fresh(), $journeys->latestFor($employee->id)))->keyBy('key');

    expect($steps()['entitlements']['state'])->toBe('todo')
        ->and($steps()['entitlements']['data']['names'])->toBe([$item->name])
        ->and(OffboardingJourneys::requiredKeys($steps()->values()->all()))->toContain('entitlements');

    app(Entitlements::class)->revoke($row, $this->hr);

    expect($steps()['entitlements']['state'])->toBe('done');
});

test('a journey without items keeps its steps', function () {
    $contract = ($this->contract)();
    $contract->update(['terminated_at' => today()->toDateString(), 'is_active' => false]);
    $employeeId = $contract->employee_id;

    $journeys = app(OffboardingJourneys::class);
    $keys = collect($journeys->steps(Employee::query()->find($employeeId), $journeys->latestFor($employeeId)))->pluck('key')->all();

    expect($keys)->toBe(OffboardingJourneys::STEPS);
});

// ── The catalog and the backfill ─────────────────────────────────────

test('the catalog is built from the positions, departments, programs and cities', function () {
    $finance = Department::factory()->create(['code' => 'Fin']);
    $micro = Program::factory()->create(['name' => 'Micro Project']);
    $projectManager = JobLevel::factory()->create(['name_en' => 'Project Manager', 'grade' => 3]);
    $list = MailList::factory()->create(['address' => 'financial@hcsyria.org']);
    $accountant = Position::factory()->create(['city_id' => $this->city->id, 'department_id' => $finance->id, 'title_en' => 'Accountant']);
    $microOfficer = Position::factory()->create(['city_id' => City::factory()->create(['slug' => 'ALP'])->id, 'program_id' => $micro->id, 'title_en' => 'Project Officer']);
    $homs = City::factory()->create(['slug' => 'HMS', 'name_en' => 'HMS']);
    $microHoms = Position::factory()->create(['city_id' => $homs->id, 'program_id' => $micro->id, 'title_en' => 'Project Officer']);

    $this->seed(EntitlementLibrarySeeder::class);

    $item = fn (string $en): Entitlement => Entitlement::query()->where('name_en', $en)->firstOrFail();
    $defaultOf = fn (Entitlement $entitlement, Position $position): bool => PositionEntitlement::query()->where('entitlement_id', $entitlement->id)->where('position_id', $position->id)->exists();

    $financial = $item('financial@ mail list');

    expect($financial->mail_list_id)->toBe($list->id)
        ->and($defaultOf($financial, $accountant))->toBeTrue()
        ->and($defaultOf($financial, $microOfficer))->toBeFalse()
        // A city list goes only to that city's positions of the program.
        ->and($defaultOf($item('micro.aleppo@ mail list'), $microOfficer))->toBeTrue()
        ->and($defaultOf($item('micro.aleppo@ mail list'), $microHoms))->toBeFalse()
        ->and($defaultOf($item('micro.homs@ mail list'), $microHoms))->toBeTrue()
        // Managers by job level; one staff group per city.
        ->and(PositionEntitlement::query()->where('entitlement_id', $item("Center's cameras")->id)->where('job_level_id', $projectManager->id)->exists())->toBeTrue()
        ->and($defaultOf($item('Staff Telegram group: HMS'), $microHoms))->toBeTrue()
        ->and($defaultOf($item('Staff Telegram group: HMS'), $accountant))->toBeFalse();

    // Running it again adds nothing and leaves an item HR stopped stopped.
    $financial->update(['is_active' => false, 'name_ar' => 'قائمة المالية']);
    $counts = [Entitlement::query()->count(), PositionEntitlement::query()->count()];
    $this->seed(EntitlementLibrarySeeder::class);

    expect([Entitlement::query()->count(), PositionEntitlement::query()->count()])->toBe($counts)
        ->and($financial->fresh()->is_active)->toBeFalse()
        ->and($financial->fresh()->name_ar)->toBe('قائمة المالية');
});

test('the backfill gives people in post their defaults and keeps what was revoked', function () {
    $contract = ($this->contract)();
    $kept = ($this->entitlement)('IT database', 'database', $this->position);
    $revoked = ($this->entitlement)('Old group', 'telegram', $this->position);
    EmployeeEntitlement::query()->create([
        'employee_id' => $contract->employee_id, 'entitlement_id' => $revoked->id,
        'source' => EmployeeEntitlement::SOURCE_DEFAULT, 'state' => EmployeeEntitlement::STATE_REVOKED,
    ]);

    $this->artisan('entitlements:backfill', ['--dry-run' => true])->assertSuccessful();
    expect(EmployeeEntitlement::query()->where('entitlement_id', $kept->id)->exists())->toBeFalse();

    $this->artisan('entitlements:backfill')->assertSuccessful();
    $this->artisan('entitlements:backfill')->assertSuccessful();

    $rows = ($this->rowsOf)($contract->employee_id);

    expect($rows[$kept->id]->state)->toBe(EmployeeEntitlement::STATE_REQUIRED)
        ->and($rows[$revoked->id]->state)->toBe(EmployeeEntitlement::STATE_REVOKED)
        ->and(EmployeeEntitlement::query()->where('employee_id', $contract->employee_id)->where('entitlement_id', $kept->id)->count())->toBe(1);
});

test('the grant migration gives HR both permissions and managers the assign one', function () {
    $migration = require database_path('migrations/2026_11_27_000002_grant_entitlement_permissions.php');
    $hrRole = Role::findOrCreate('HR-TST', 'web');
    $managerRole = Role::findOrCreate('manager', 'web');

    $migration->up();

    expect($hrRole->fresh()->hasPermissionTo('entitlements.manage-library'))->toBeTrue()
        ->and($hrRole->fresh()->hasPermissionTo('entitlements.assign'))->toBeTrue()
        ->and($managerRole->fresh()->hasPermissionTo('entitlements.assign'))->toBeTrue()
        ->and($managerRole->fresh()->hasPermissionTo('entitlements.manage-library'))->toBeFalse();
});
