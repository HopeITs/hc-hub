<?php

use Carbon\CarbonImmutable;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Leave\TeamCoverage;
use Modules\Hr\Support\Settings\HrAccess;

/*
| "Who covers" on the team calendar: a manager sees, on each absence that
| names a cover, the days the cover is away too. "Today" is Wednesday
| 2026-10-21; 3-5 Nov 2026 is Tuesday to Thursday.
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-10-21 10:00:00');

    $managerEmployee = Employee::factory()->create();
    $managerPosition = Position::factory()->create();
    Contract::factory()->for($managerEmployee)->for($managerPosition)->create(['is_active' => true]);
    $this->manager = User::factory()->create(['employee_id' => $managerEmployee->id])->fresh();

    $report = function () use ($managerPosition): Contract {
        $employee = Employee::factory()->create();

        return Contract::factory()->for($employee)->for(Position::factory()->create(['direct_manager_id' => $managerPosition->id]))->create(['is_active' => true]);
    };

    $this->away = $report();
    $this->cover = $report();

    $this->leave = fn (Contract $contract, string $from, string $to, LeaveRequestStatus $status, ?int $coverId = null): LeaveRequest => LeaveRequest::factory()
        ->for(LeaveBalance::factory()->for($contract))
        ->create(['type' => 'day', 'status' => $status->value, 'start_at' => $from, 'end_at' => $to, 'alternative_employee_id' => $coverId]);

    $this->entryOfAway = fn (): array => app(TeamCoverage::class)
        ->absences($this->manager, 'reports', CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'))
        ->firstWhere('substitute_id', $this->cover->employee_id);
});

test('the days the cover is away too are flagged on the absence', function () {
    ($this->leave)($this->away, '2026-11-03', '2026-11-05', LeaveRequestStatus::Accepted, $this->cover->employee_id);
    ($this->leave)($this->cover, '2026-11-04', '2026-11-04', LeaveRequestStatus::Accepted);

    expect(($this->entryOfAway)()['cover_gap_dates'])->toBe(['2026-11-04']);
});

test("a cover's pending leave counts for whoever decides it, and a cover who stays leaves no gap", function () {
    ($this->leave)($this->away, '2026-11-03', '2026-11-05', LeaveRequestStatus::Accepted, $this->cover->employee_id);

    expect(($this->entryOfAway)()['cover_gap_dates'])->toBe([]);

    ($this->leave)($this->cover, '2026-11-05', '2026-11-05', LeaveRequestStatus::Pending);
    app()->forgetInstance(TeamCoverage::class);

    expect(($this->entryOfAway)()['cover_gap_dates'])->toBe(['2026-11-05']);
});
