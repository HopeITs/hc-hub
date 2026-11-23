<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\CriticalPositions\CandidateSuggestion;
use Modules\Hr\Support\CriticalPositions\CriticalPositionAlerts;
use Modules\Hr\Support\CriticalPositions\InternalCandidateRanker;
use Modules\Hr\Support\CriticalPositions\PastApplicantsSource;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| Critical positions: the tag, when a critical position needs a replacement,
| the daily alert to HR and the direct manager, and the suggested-replacements
| panel. "Today" is Wednesday 2026-10-21. Nothing here calls an outside service.
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-10-21 10:00:00');
    Notification::fake();

    ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);

    $this->city = City::factory()->create();
    $this->department = Department::factory()->create();
    $this->grade = JobLevel::factory()->create(['grade' => 4]);
    $this->contractFields = ['contract_type_id' => 1, 'start_date' => '2024-01-01', 'end_date' => '2026-12-31', 'is_active' => true];

    $this->managerPosition = Position::factory()->create(['city_id' => $this->city->id]);
    $this->manager = User::factory()->create();
    Contract::factory()->create(['employee_id' => $this->manager->employee_id, 'position_id' => $this->managerPosition->id] + $this->contractFields);

    $this->hr = User::factory()->role('HR-'.strtoupper($this->city->slug))->create();

    foreach (['positions.mark-critical', 'positions.view-replacements'] as $permission) {
        $this->hr->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $this->hr = $this->hr->fresh();

    $this->critical = Position::factory()->create([
        'city_id' => $this->city->id, 'department_id' => $this->department->id, 'job_level_id' => $this->grade->id,
        'direct_manager_id' => $this->managerPosition->id, 'is_critical' => true, 'qualifications' => 'Accounting and budgeting',
    ]);

    $this->critical = $this->critical->fresh();

    $this->kinds = fn (User $user): array => Notification::sent($user, JourneyNotice::class)
        ->map(fn (JourneyNotice $notice): string => $notice->kind)
        ->filter(fn (string $kind): bool => str_starts_with($kind, 'critical-position-'))
        ->values()
        ->all();
});

test('a vacant critical position tells city HR and the direct manager, once', function () {
    $this->artisan('positions:notify-critical')->assertSuccessful();
    $this->artisan('positions:notify-critical')->assertSuccessful();

    expect(($this->kinds)($this->hr))->toBe(['critical-position-vacant'])
        ->and(($this->kinds)($this->manager))->toBe(['critical-position-vacant']);
});

test('a position that is not critical, not active or held tells nobody', function () {
    Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $this->managerPosition->id]);
    Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $this->managerPosition->id, 'is_critical' => true, 'is_active' => false]);
    Contract::factory()->create(['employee_id' => Employee::factory()->create()->id, 'position_id' => $this->critical->id] + $this->contractFields);

    $this->artisan('positions:notify-critical')->assertSuccessful();

    expect(($this->kinds)($this->hr))->toBe([])
        ->and(app(CriticalPositionAlerts::class)->needsReplacement($this->critical))->toBeFalse();
});

test('a holder whose contract ended is a different reason, told once per episode', function () {
    $holder = Contract::factory()->create(['employee_id' => Employee::factory()->create()->id, 'position_id' => $this->critical->id, 'end_date' => '2026-09-30', 'is_active' => false] + array_diff_key($this->contractFields, ['end_date' => 1, 'is_active' => 1]));

    expect(app(CriticalPositionAlerts::class)->reasonsFor($this->critical))->toBe(['contract_ended']);

    $this->artisan('positions:notify-critical')->assertSuccessful();
    $this->artisan('positions:notify-critical')->assertSuccessful();

    expect(($this->kinds)($this->hr))->toBe(['critical-position-contract-ended']);

    $holder->update(['end_date' => '2026-12-31', 'is_active' => true]);
    expect(app(CriticalPositionAlerts::class)->needsReplacement($this->critical))->toBeFalse();
});

test('only HR, the general manager and the administrators open the panel, never a direct manager', function () {
    $general = User::factory()->role('General Manager')->create();
    $general->givePermissionTo(Permission::findOrCreate('positions.view-replacements', 'web'));
    $manager = $this->manager->fresh();
    $manager->givePermissionTo(Permission::findOrCreate('positions.view-replacements', 'web'));

    expect(Gate::forUser($this->hr)->allows('viewReplacements', $this->critical))->toBeTrue()
        ->and(Gate::forUser($general->fresh())->allows('viewReplacements', $this->critical))->toBeTrue()
        ->and(Gate::forUser($manager->fresh())->allows('viewReplacements', $this->critical))->toBeFalse();

    $notCritical = Position::factory()->create(['city_id' => $this->city->id]);

    expect(Gate::forUser($this->hr)->allows('viewReplacements', $notCritical))->toBeFalse();

    Livewire::actingAs($manager->fresh())->test('hr::positions.replacements', ['position' => $this->critical->id])->assertForbidden();
});

test('HR marks and clears the tag from the position form; others cannot', function () {
    $plain = Position::factory()->create(['city_id' => $this->city->id]);
    $this->hr->givePermissionTo(Permission::findOrCreate('positions.view', 'web'), Permission::findOrCreate('positions.update', 'web'));

    Livewire::actingAs($this->hr->fresh())->test('hr::positions.index')
        ->call('openPosition', $plain->id)
        ->assertSeeHtml('data-critical-toggle')
        ->call('toggleCritical');

    expect($plain->fresh()->is_critical)->toBeTrue();

    Livewire::actingAs($this->hr->fresh())->test('hr::positions.index')
        ->call('openPosition', $plain->id)
        ->call('toggleCritical');

    expect($plain->fresh()->is_critical)->toBeFalse();

    $editor = User::factory()->create();
    $editor->givePermissionTo(Permission::findOrCreate('positions.view', 'web'), Permission::findOrCreate('positions.update', 'web'));

    Livewire::actingAs($editor->fresh())->test('hr::positions.index')
        ->call('openPosition', $plain->id)
        ->assertDontSeeHtml('data-critical-toggle')
        ->call('toggleCritical')
        ->assertForbidden();

    expect($plain->fresh()->is_critical)->toBeFalse();
});

test('internal suggestions are ranked by explainable rules and leave out holders and other critical positions', function () {
    $sameFit = Employee::factory()->create(['city_id' => $this->city->id, 'is_active' => true]);
    Education::query()->create(['employee_id' => $sameFit->id, 'name' => 'Accounting', 'specialization' => 'Budgeting']);
    $closeHeld = Position::factory()->create(['city_id' => $this->city->id, 'department_id' => $this->department->id, 'job_level_id' => $this->grade->id]);
    Contract::factory()->create(['employee_id' => $sameFit->id, 'position_id' => $closeHeld->id] + $this->contractFields);

    $farFit = Employee::factory()->create(['city_id' => $this->city->id, 'is_active' => true]);
    $farHeld = Position::factory()->create(['city_id' => $this->city->id, 'job_level_id' => JobLevel::factory()->create(['grade' => 9])->id]);
    Contract::factory()->create(['employee_id' => $farFit->id, 'position_id' => $farHeld->id] + $this->contractFields);

    $otherCritical = Employee::factory()->create(['city_id' => $this->city->id, 'is_active' => true]);
    $otherCriticalPosition = Position::factory()->create(['city_id' => $this->city->id, 'department_id' => $this->department->id, 'job_level_id' => $this->grade->id, 'is_critical' => true]);
    Contract::factory()->create(['employee_id' => $otherCritical->id, 'position_id' => $otherCriticalPosition->id] + $this->contractFields);

    $suggestions = app(InternalCandidateRanker::class)->suggest($this->critical, $this->hr, 10);

    expect(collect($suggestions)->pluck('id')->all())->toBe([$sameFit->id])
        ->and($suggestions[0]->score)->toBeGreaterThanOrEqual(60)
        ->and($suggestions[0]->source)->toBe(CandidateSuggestion::INTERNAL)
        ->and($suggestions[0]->reasonTexts())->not->toBeEmpty();
});

test('earlier CVs are closed to anyone without the recruitment applicant right', function () {
    expect(app(PastApplicantsSource::class)->suggest($this->critical, $this->manager->fresh(), 10))->toBe([]);
});

test('the daily alert is scheduled in the morning', function () {
    $events = collect(app(Schedule::class)->events())
        ->mapWithKeys(fn ($event): array => [trim((string) preg_replace('/^.*artisan"?\s+/', '', (string) $event->command)) => $event]);

    expect($events['positions:notify-critical']->expression)->toBe('40 7 * * *');
});
