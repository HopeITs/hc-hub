<?php

use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Desk\Cards\CalendarCard;
use Spatie\Permission\Models\Permission;

/*
| The HR calendar holds the next thirty days of the viewer's team as well as
| their own: a teammate's accepted leave and birthday, and, for whoever
| oversees contracts, the contracts ending.
*/

beforeEach(function () {
    $this->travelTo('2026-11-11 09:00:00');
    $this->colleague = Employee::factory()->create(['first_name_en' => 'Lina', 'last_name_en' => 'Saleh', 'actual_dob' => '1990-11-20']);
    $contract = Contract::factory()->for($this->colleague)->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-01', 'is_active' => true]);
    LeaveRequest::factory()->for(LeaveBalance::factory()->for($contract))->create(['start_at' => '2026-11-16', 'end_at' => '2026-11-17', 'status' => LeaveRequestStatus::Accepted->value]);
    LeaveRequest::factory()->for(LeaveBalance::factory()->for($contract))->create(['start_at' => '2026-11-18', 'end_at' => '2026-11-18', 'status' => LeaveRequestStatus::Rejected->value]);
});

test('the calendar shows a teammate\'s leave, birthday and ending contract to whoever oversees contracts', function () {
    $viewer = User::factory()->role('Super Admin')->create(['employee_id' => Employee::factory()->create()->id]);
    $viewer->givePermissionTo(Permission::findOrCreate('contracts.view'));

    $kinds = (new CalendarCard)->rows($viewer)->map(fn (array $row): string => $row['kind'].': '.$row['title'])->all();

    expect($kinds)->toBe([
        __('hr_desk.cards.calendar.kind.team-leave').': Lina Saleh',
        __('hr_desk.cards.calendar.kind.birthday').': Lina Saleh',
        __('hr_desk.cards.calendar.kind.contract').': Lina Saleh',
    ]);
});

test('the calendar leaves ending contracts out for someone who may not view contracts', function () {
    $viewer = User::factory()->role('Super Admin')->create(['employee_id' => Employee::factory()->create()->id]);

    expect((new CalendarCard)->rows($viewer)->pluck('key')->filter(fn (string $key): bool => str_starts_with($key, 'cl-c-'))->all())->toBe([]);
});
