<?php

use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Models\PolicyAcknowledgement;
use Modules\Hr\Models\PolicyDocument;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;
use Modules\Hr\Support\Inbox\WorkInbox;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Journeys\OnboardingJourneys;
use Modules\Hr\Support\Journeys\PolicyAcknowledgements;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| The extended onboarding journey: after the contract every step runs side by
| side (login account, electronics, ID card, HR's session, the manager's
| session, policies, the guideline), the electronics come from IT of the city,
| deadlines are 2 and 14 days from the contract start, the Mug is outside the
| journey, and completion covers all of it. "Today" is 2026-10-21 (Wednesday).
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-10-21 10:00:00');
    Notification::fake();

    foreach ([1, 3] as $id) {
        ContractType::query()->find($id) ?? ContractType::factory()->create(['id' => $id]);
    }

    $this->city = City::factory()->create(['code' => '001']);
    $this->program = Program::factory()->unassigned()->create(['code' => '010']);
    $this->itDepartment = Department::factory()->create(['code' => 'IT']);

    $this->managerPosition = Position::factory()->create(['city_id' => $this->city->id, 'program_id' => $this->program->id]);
    $this->position = Position::factory()->create(['city_id' => $this->city->id, 'program_id' => $this->program->id, 'direct_manager_id' => $this->managerPosition->id]);
    $this->itPosition = Position::factory()->create(['city_id' => $this->city->id, 'program_id' => $this->program->id, 'department_id' => $this->itDepartment->id]);

    $this->contractFields = ['contract_type_id' => 1, 'start_date' => '2026-10-21', 'end_date' => '2027-10-20', 'is_active' => true];

    $this->managerUser = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $this->city->id])->id]);
    Contract::factory()->create(['employee_id' => $this->managerUser->employee_id, 'position_id' => $this->managerPosition->id] + $this->contractFields);

    $this->hr = User::factory()->role('HR-'.strtoupper($this->city->slug))->create();
    $this->hr->givePermissionTo(Permission::findOrCreate('employees.update', 'web'));
    $this->hr = $this->hr->fresh();

    $this->newIt = function (): Employee {
        $employee = Employee::factory()->create(['city_id' => $this->city->id]);
        Contract::factory()->create(['employee_id' => $employee->id, 'position_id' => $this->itPosition->id] + $this->contractFields);
        User::factory()->create(['employee_id' => $employee->id]);

        return $employee;
    };

    $this->volunteer = function (array $contract = []): Employee {
        $employee = Employee::factory()->create(['city_id' => $this->city->id]);
        $created = Contract::factory()->create(['employee_id' => $employee->id, 'position_id' => $this->position->id] + $contract + $this->contractFields);
        User::factory()->create(['employee_id' => $employee->id]);
        app(OnboardingJourneys::class)->startForContract($employee, $created);

        return $employee->fresh();
    };

    // The migration already seeds a few items (Mouse among them): reuse a name, never duplicate it.
    $this->item = fn (string $name, string $group): ChecklistItem => ChecklistItem::query()->updateOrCreate(['name' => $name], ['order' => 1, 'onboarding_group' => $group]);

    $this->deliver = function (Employee $volunteer, ChecklistItem $item, Employee $deliverer): void {
        $row = Checklist::query()->create(['item_id' => $item->id, 'employee_id' => $volunteer->id, 'delivered_id' => $deliverer->id, 'delivered_status' => 'pending']);
        app(ChecklistWorkflow::class)->markDelivered($row->id, $deliverer->id, null);
    };

    $this->step = fn (Employee $volunteer, string $key): array => collect(app(OnboardingJourneys::class)->steps($volunteer->fresh(), $volunteer->fresh()->onboardingJourney))->firstWhere('key', $key);

    $this->policy = fn (string $title, bool $required, string $kind = PolicyDocument::KIND_POLICY): PolicyDocument => PolicyDocument::query()->create([
        'source' => PolicyDocument::SOURCE_PANEL, 'title' => $title, 'required_for_onboarding' => $required, 'onboarding_kind' => $kind,
    ]);
});

test('the manager schedules then records the session, which is due fourteen days after the start', function () {
    $volunteer = ($this->volunteer)();
    $journeys = app(OnboardingJourneys::class);

    expect(OnboardingJourney::query()->sole()->orientation_due_on->toDateString())->toBe('2026-11-04')
        ->and($journeys->completeManagerOrientation($volunteer, $this->managerUser))->toBeFalse()
        ->and($journeys->scheduleManagerOrientation($volunteer, now()->addDay(), $this->managerUser))->toBeTrue()
        ->and($journeys->scheduleManagerOrientation($volunteer, now()->addDay(), $this->managerUser))->toBeFalse()
        ->and($journeys->completeManagerOrientation($volunteer, $this->managerUser))->toBeFalse();

    $this->travelTo(now()->addDay());

    expect($journeys->completeManagerOrientation($volunteer, $this->managerUser))->toBeTrue()
        ->and($journeys->completeManagerOrientation($volunteer, $this->managerUser))->toBeFalse()
        ->and((int) OnboardingJourney::query()->sole()->manager_orientation_completed_by)->toBe($this->managerUser->id);
});

test('only the direct manager or city HR may run the manager session', function () {
    $volunteer = ($this->volunteer)();
    $journeys = app(OnboardingJourneys::class);
    $colleague = User::factory()->create();

    expect($journeys->scheduleManagerOrientation($volunteer, now(), $colleague))->toBeFalse()
        ->and(OnboardingJourney::query()->sole()->manager_orientation_scheduled_for)->toBeNull()
        ->and($journeys->scheduleManagerOrientation($volunteer, now(), $this->hr))->toBeTrue();

    Livewire::actingAs($colleague)->test('hr::onboarding.manager-session', ['employee' => $volunteer->id])->assertForbidden();

    Livewire::actingAs($this->managerUser)->test('hr::onboarding.manager-session', ['employee' => $volunteer->id])
        ->set('date', '2026-10-21')
        ->call('schedule')
        ->assertHasNoErrors()
        ->call('complete');

    expect(OnboardingJourney::query()->sole()->manager_orientation_completed_at)->not->toBeNull();
});

test('the managers session waits in the managers inbox, late after the fourteen days', function () {
    $volunteer = ($this->volunteer)();

    $row = fn () => app(WorkInbox::class)->for($this->managerUser->fresh())['rows']->first(fn (array $row): bool => str_starts_with($row['key'], 'onboarding-manager_schedule'));

    expect($row()['href'])->toBe(route('hr.onboarding.manager-session', ['employee' => $volunteer->id]))
        ->and($row()['urgent'])->toBeFalse();

    $this->travelTo('2026-11-06 10:00:00');
    $this->managerUser = $this->managerUser->fresh();

    expect(app(WorkInbox::class)->for(User::query()->find($this->managerUser->id))['rows']->first(fn (array $row): bool => str_starts_with($row['key'], 'onboarding-manager_schedule'))['urgent'])->toBeTrue();
});

test('the electronics are handed over by IT of the city: one is automatic, several wait for HR, none blocks', function () {
    $volunteer = ($this->volunteer)();
    ($this->item)('Laptop', ChecklistItem::GROUP_ELECTRONICS);
    ($this->item)('Mouse', ChecklistItem::GROUP_ELECTRONICS);
    $journeys = app(OnboardingJourneys::class);

    expect(($this->step)($volunteer, 'electronics'))->state->toBe('problem')->note->toBe('electronics-no-it');

    $first = ($this->newIt)();

    expect(($this->step)($volunteer, 'electronics'))->state->toBe('todo')->note->toBeNull()
        ->and(($this->step)($volunteer, 'electronics')['data']['deliverer']->id)->toBe($first->id);

    $second = ($this->newIt)();

    expect(($this->step)($volunteer, 'electronics'))->owner->toBe('hr')->note->toBe('electronics-choose-it')
        ->and($journeys->chooseElectronicsDeliverer($volunteer, $first->id + 999, $this->hr))->toBeFalse()
        ->and($journeys->chooseElectronicsDeliverer($volunteer, $second->id, $this->hr))->toBeTrue()
        ->and(($this->step)($volunteer, 'electronics')['data']['deliverer']->id)->toBe($second->id);
});

test('electronics finish only when every electronics item is delivered, and a delivery settles the journey', function () {
    $volunteer = ($this->volunteer)();
    $it = ($this->newIt)();
    $laptop = ($this->item)('Laptop', ChecklistItem::GROUP_ELECTRONICS);
    $mouse = ($this->item)('Mouse', ChecklistItem::GROUP_ELECTRONICS);

    ($this->deliver)($volunteer, $laptop, $it);

    expect(($this->step)($volunteer, 'electronics')['state'])->toBe('todo')
        ->and(($this->step)($volunteer, 'electronics')['data']['missing']->pluck('name')->all())->toBe(['Mouse']);

    ($this->deliver)($volunteer, $mouse, $it);

    expect(($this->step)($volunteer, 'electronics')['state'])->toBe('done');
});

test('the card step needs the printed card and its items', function () {
    $volunteer = ($this->volunteer)();
    $deliverer = ($this->newIt)();
    DefaultDeliverable::query()->create(['employee_id' => $deliverer->id, 'city_id' => $this->city->id, 'item_id' => ($cartoon = ($this->item)('ID Cartoon', ChecklistItem::GROUP_CARD))->id]);

    expect(app(OnboardingJourneys::class)->markCardHandedOver($volunteer, $this->hr))->toBeTrue()
        ->and(($this->step)($volunteer, 'card')['state'])->toBe('todo')
        ->and(($this->step)($volunteer, 'card')['note'])->toBe('card-items-missing');

    ($this->deliver)($volunteer, $cartoon, $deliverer);

    expect(($this->step)($volunteer, 'card')['state'])->toBe('done');
});

test('account, electronics and card are late after two days, the sessions after fourteen', function () {
    $volunteer = ($this->volunteer)();
    $late = fn (string $key): bool => ($this->step)($volunteer, $key)['late'];

    $this->travelTo('2026-10-23 10:00:00');
    expect(array_map($late, ['account', 'electronics', 'card', 'orientation', 'manager_orientation']))->toBe([false, false, false, false, false]);

    $this->travelTo('2026-10-24 10:00:00');
    expect(array_map($late, ['electronics', 'card', 'orientation', 'manager_orientation']))->toBe([true, true, false, false]);

    $this->travelTo('2026-11-05 10:00:00');
    expect($late('orientation'))->toBeTrue()->and($late('manager_orientation'))->toBeTrue();
});

test('overdue steps remind their owner every week until done', function () {
    ($this->volunteer)();
    $it = ($this->newIt)();
    ($this->item)('Laptop', ChecklistItem::GROUP_ELECTRONICS);
    $itUser = User::query()->where('employee_id', $it->id)->first();

    $kinds = fn (User $user): array => Notification::sent($user, JourneyNotice::class)->map(fn (JourneyNotice $notice): string => $notice->kind)->values()->all();

    $this->artisan('hr:send-onboarding-step-reminders')->assertSuccessful();
    expect($kinds($this->hr))->toBe([]);

    $this->travelTo('2026-10-24 10:00:00');
    $this->artisan('hr:send-onboarding-step-reminders')->assertSuccessful();
    $this->artisan('hr:send-onboarding-step-reminders')->assertSuccessful();

    expect($kinds($this->hr))->toContain('onboarding-card-overdue')
        ->and($kinds($itUser))->toBe(['onboarding-electronics-overdue']);

    // The same week sends nothing more; a week later, one more each.
    $this->travelTo('2026-10-26 10:00:00');
    $this->artisan('hr:send-onboarding-step-reminders')->assertSuccessful();
    expect($kinds($itUser))->toHaveCount(1);

    $this->travelTo('2026-10-31 10:00:00');
    $this->artisan('hr:send-onboarding-step-reminders')->assertSuccessful();
    expect($kinds($itUser))->toHaveCount(2);

    // The manager hears about their own session once it is late (fourteen days).
    $this->travelTo('2026-11-06 10:00:00');
    $this->artisan('hr:send-onboarding-step-reminders')->assertSuccessful();
    expect($kinds($this->managerUser))->toBe(['onboarding-manager-orientation-overdue']);
});

test('the mug is not due during a trial and never holds the journey back', function () {
    $deliverer = ($this->newIt)();
    DefaultDeliverable::query()->create(['employee_id' => $deliverer->id, 'city_id' => $this->city->id, 'item_id' => ($mug = ($this->item)('Mug', ChecklistItem::GROUP_AFTER_PROBATION))->id]);
    $journeys = app(OnboardingJourneys::class);

    $volunteer = ($this->volunteer)(['contract_type_id' => 3]);

    expect($journeys->mug($volunteer)['due'])->toBeFalse()
        ->and(array_column($journeys->steps($volunteer, $volunteer->onboardingJourney), 'key'))->not->toContain('mug');

    Contract::factory()->create(['employee_id' => $volunteer->id, 'position_id' => $this->position->id, 'contract_type_id' => 1, 'start_date' => '2026-10-22', 'end_date' => '2027-04-22', 'is_active' => true]);
    $this->travelTo('2026-10-23 10:00:00');

    expect($journeys->mug($volunteer->fresh())['due'])->toBeTrue();

    ($this->deliver)($volunteer, $mug, $deliverer);

    expect($journeys->mug($volunteer->fresh())['delivered'])->toBeTrue();
});

test('policies and the guideline are separate steps, a problem until HR names one, done when agreed for oneself', function () {
    $volunteer = ($this->volunteer)();
    $other = ($this->volunteer)();
    $volunteerUser = User::query()->where('employee_id', $volunteer->id)->first();
    ($this->policy)('Optional', false);

    expect(($this->step)($volunteer, 'policies'))->state->toBe('problem')->note->toBe('policies-none-required')
        ->and(($this->step)($volunteer, 'guideline')['state'])->toBe('problem');

    $code = ($this->policy)('Code of conduct', true);
    $media = ($this->policy)('Media guideline', true, PolicyDocument::KIND_REVIEW);

    $page = Livewire::actingAs($volunteerUser)->test('hr::onboarding.policies')
        ->assertSee('Code of conduct')->assertSee('Media guideline')->assertDontSee('Optional')
        ->call('acknowledge', $code->id)
        ->call('acknowledge', $code->id);

    expect(PolicyAcknowledgement::query()->where('employee_id', $volunteer->id)->count())->toBe(1)
        ->and(($this->step)($volunteer, 'policies')['state'])->toBe('done')
        ->and(($this->step)($volunteer, 'guideline')['state'])->toBe('todo')
        ->and(($this->step)($other, 'policies')['state'])->toBe('todo');

    $page->call('acknowledge', $media->id);

    expect(($this->step)($volunteer, 'guideline')['state'])->toBe('done')
        ->and(PolicyAcknowledgement::query()->where('employee_id', $other->id)->count())->toBe(0);
});

test('HR marks which documents are required and of which kind, and nobody else may', function () {
    $doc = ($this->policy)('Media guideline', false);

    Livewire::actingAs($this->managerUser)->test('hr::onboarding.required-policies')->assertForbidden();

    Livewire::actingAs($this->hr)->test('hr::onboarding.required-policies')
        ->call('setRequired', $doc->id, true)
        ->call('setKind', $doc->id, PolicyDocument::KIND_REVIEW)
        ->call('setKind', $doc->id, 'nonsense');

    expect($doc->fresh())->required_for_onboarding->toBeTrue()->onboarding_kind->toBe(PolicyDocument::KIND_REVIEW)
        ->and(app(PolicyAcknowledgements::class)->required(PolicyDocument::KIND_REVIEW))->toHaveCount(1);
});

test('completion follows every step and unwinds when one is undone, and an older complete journey stays complete', function () {
    $volunteer = ($this->volunteer)();
    $it = ($this->newIt)();
    $hr = $this->hr;
    $journeys = app(OnboardingJourneys::class);
    ($this->item)('Laptop', ChecklistItem::GROUP_ELECTRONICS);
    $items = ChecklistItem::query()->where('onboarding_group', ChecklistItem::GROUP_ELECTRONICS)->get();
    $docs = [($this->policy)('Code', true), ($this->policy)('Media', true, PolicyDocument::KIND_REVIEW)];

    $journeys->scheduleOrientation($volunteer, now(), $hr);
    $journeys->completeOrientation($volunteer, $hr);
    $journeys->scheduleManagerOrientation($volunteer, now(), $this->managerUser);
    $journeys->completeManagerOrientation($volunteer, $this->managerUser);
    $journeys->markCardHandedOver($volunteer, $hr);

    expect(OnboardingJourney::query()->sole()->completed_at)->toBeNull();

    foreach ($items as $item) {
        ($this->deliver)($volunteer, $item, $it);
    }

    foreach ($docs as $doc) {
        app(PolicyAcknowledgements::class)->acknowledge($volunteer, $doc);
    }

    expect(OnboardingJourney::query()->sole()->completed_at)->not->toBeNull();

    $journeys->reopen($volunteer, 'manager_orientation', $hr);

    expect(OnboardingJourney::query()->sole()->completed_at)->toBeNull();

    // A journey that finished before the newer steps existed is not reopened by them.
    OnboardingJourney::query()->update(['completed_at' => now(), 'manager_orientation_completed_at' => null]);
    app(OnboardingJourneys::class)->settle($volunteer);

    expect(OnboardingJourney::query()->sole()->completed_at)->not->toBeNull();
});

test('my first days shows a volunteer their own journey, worded for them, without the HR record step', function () {
    app()->setLocale('en');
    $volunteer = ($this->volunteer)();
    $account = User::query()->where('employee_id', $volunteer->id)->sole();

    Livewire::actingAs($account)->test('hr::onboarding.my-first-days')
        ->assertOk()
        ->assertSee('Your contract')
        ->assertSee('Read and agree to the policies')
        ->assertSeeHtml('data-step="policies"')
        ->assertDontSeeHtml('data-step="record"');

    $this->actingAs($account)->get(route('hr.onboarding.my-first-days'))->assertOk();
    Livewire::actingAs(User::factory()->create(['employee_id' => null]))->test('hr::onboarding.my-first-days')->assertForbidden();
});

test('the migration puts only the order-3 rows of its named items in the exit group, and rollback removes the Mouse it added', function () {
    ChecklistItem::query()->where('name', 'Mouse')->delete();
    $returned = ChecklistItem::query()->create(['name' => 'Laptop', 'order' => 3, 'onboarding_group' => 'other']);
    $unknown = ChecklistItem::query()->create(['name' => 'Parking card', 'order' => 3, 'onboarding_group' => 'other']);

    $migration = require base_path('modules/Hr/database/migrations/2026_11_25_000001_extend_onboarding_journeys.php');
    $migration->up();

    expect($returned->fresh()->onboarding_group)->toBe('exit')
        ->and($unknown->fresh()->onboarding_group)->toBe('other')
        ->and(ChecklistItem::query()->where('name', 'Mouse')->count())->toBe(1);

    $migration->down();

    expect(ChecklistItem::query()->where('name', 'Mouse')->exists())->toBeFalse();
});
