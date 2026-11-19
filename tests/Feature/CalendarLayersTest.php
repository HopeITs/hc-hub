<?php

use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Calendar\LeaveCalendarLayer;
use Spatie\Permission\Models\Permission;

/*
| One calendar with layers (roadmap 3.4): beside the viewer's own work, the
| calendar page shows what other modules register as layers. HR adds the
| holidays that apply to the viewer and the accepted leave of the viewer and
| their team; each layer can be switched off, and one the viewer may not see
| is never offered.
*/

beforeEach(function () {
    $this->travelTo('2026-11-11 09:00:00');

    $this->employee = Employee::factory()->create(['first_name_en' => 'Lina', 'last_name_en' => 'Saleh']);
    $this->viewer = User::factory()->role('General Manager')->create(['employee_id' => $this->employee->id]);
    $this->contract = Contract::factory()->for($this->employee)->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
});

/**
 * A leave of the viewer's own, on their running contract.
 */
function viewerLeave(string $from, string $to, LeaveRequestStatus $status = LeaveRequestStatus::Accepted): LeaveRequest
{
    return LeaveRequest::factory()->for(LeaveBalance::factory()->for(test()->contract))->create(['start_at' => $from, 'end_at' => $to, 'status' => $status->value]);
}

function nationalHoliday(string $date): Holiday
{
    return Holiday::query()->create([
        'name_en' => 'Founding Day',
        'name_ar' => 'يوم التأسيس',
        'start_date' => $date,
        'end_date' => $date,
        'is_recurring' => false,
        'scope' => Holiday::SCOPE_NATIONAL,
        'kind' => Holiday::KIND_OFFICIAL,
    ]);
}

function mayViewOwnLeave(User $user): User
{
    $user->givePermissionTo(Permission::findOrCreate('leaves.view-own', 'web'));

    return $user;
}

test('the viewer\'s accepted leave shows on every day it covers, and a rejected one does not', function () {
    mayViewOwnLeave($this->viewer);
    $accepted = viewerLeave('2026-11-16', '2026-11-17');
    $rejected = viewerLeave('2026-11-19', '2026-11-19', LeaveRequestStatus::Rejected);

    Livewire::actingAs($this->viewer)
        ->test('tracking::calendar.index')
        ->set('month', '2026-11')
        ->assertSee('data-test="calendar-layer-leave-leave-'.$accepted->id.'-2026-11-16"', false)
        ->assertSee('data-test="calendar-layer-leave-leave-'.$accepted->id.'-2026-11-17"', false)
        ->assertDontSee('calendar-layer-leave-leave-'.$accepted->id.'-2026-11-18', false)
        ->assertDontSee('calendar-layer-leave-leave-'.$rejected->id.'-', false);
});

test('a national holiday shows on its day', function () {
    $holiday = nationalHoliday('2026-11-23');

    Livewire::actingAs($this->viewer)
        ->test('tracking::calendar.index')
        ->set('month', '2026-11')
        ->assertSee('data-test="calendar-layer-holiday-holiday-'.$holiday->id.'-2026-11-23"', false)
        ->assertSee('Founding Day')
        ->assertDontSee('calendar-layer-holiday-holiday-'.$holiday->id.'-2026-11-24', false);
});

test('switching the leave layer off hides leave and keeps holidays, and an unknown layer changes nothing', function () {
    mayViewOwnLeave($this->viewer);
    $leave = viewerLeave('2026-11-16', '2026-11-16');
    $holiday = nationalHoliday('2026-11-23');

    Livewire::actingAs($this->viewer)
        ->test('tracking::calendar.index')
        ->set('month', '2026-11')
        ->call('toggleLayer', 'payroll')
        ->assertSet('hiddenLayers', [])
        ->assertSee('calendar-layer-leave-leave-'.$leave->id.'-2026-11-16', false)
        ->call('toggleLayer', 'leave')
        ->assertSet('hiddenLayers', ['leave'])
        ->assertDontSee('calendar-layer-leave-leave-'.$leave->id.'-2026-11-16', false)
        ->assertSee('calendar-layer-holiday-holiday-'.$holiday->id.'-2026-11-23', false);
});

test('a viewer who may not view leave is offered no leave layer, though the leave exists', function () {
    $leave = viewerLeave('2026-11-16', '2026-11-16');

    Livewire::actingAs($this->viewer)
        ->test('tracking::calendar.index')
        ->set('month', '2026-11')
        ->set('showTypeFilterMenu', true)
        ->assertSee('data-test="calendar-layer-toggle-holiday"', false)
        ->assertDontSee('calendar-layer-toggle-leave', false)
        ->assertDontSee('calendar-layer-leave-leave-'.$leave->id.'-', false);
});

test('a leave that began before the days on screen is cut to those days', function () {
    mayViewOwnLeave($this->viewer);
    $leave = viewerLeave('2026-11-08', '2026-11-11');

    $days = app(LeaveCalendarLayer::class)->between($this->viewer, CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-14'));

    expect(array_keys($days))->toBe(['2026-11-10', '2026-11-11'])
        ->and($days['2026-11-10'])->toHaveCount(1)
        ->and($days['2026-11-10'][0])->toMatchArray(['id' => 'leave-'.$leave->id, 'title' => 'Lina Saleh']);
});
