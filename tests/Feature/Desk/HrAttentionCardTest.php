<?php

use App\Support\Desk\Panels;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Desk\Cards\AttentionCard;

/*
| Needs your attention (hr-attention): the viewer's own contract ending within
| thirty days with nothing after it, and their own leave requests still
| waiting although their first day has passed.
*/

beforeEach(function () {
    $this->travelTo('2026-11-11 09:00:00');
    $this->employee = Employee::factory()->create();
    $this->user = User::factory()->create(['employee_id' => $this->employee->id]);
    $this->contract = Contract::factory()->for($this->employee)->create(['start_date' => '2026-01-01', 'end_date' => '2026-11-21', 'is_active' => true]);
});

test('a contract ending within thirty days with nothing after it needs attention, a renewed one does not', function () {
    expect((new AttentionCard)->rows($this->user)->pluck('title')->all())->toBe([__('hr_desk.cards.attention.contract')])
        ->and((new AttentionCard)->health($this->user))->toBe('warn');

    Contract::factory()->for($this->employee)->create(['start_date' => '2026-11-22', 'end_date' => '2027-11-21', 'is_active' => false]);

    expect((new AttentionCard)->rows($this->user))->toBeEmpty();
});

test('a leave request still waiting after its first day needs attention, an accepted or future one does not', function () {
    $this->contract->update(['end_date' => '2027-06-30']);
    $balance = LeaveBalance::factory()->for($this->contract)->create();
    $leave = fn (string $start, LeaveRequestStatus $status) => LeaveRequest::factory()->for($balance)->create(['start_at' => $start, 'end_at' => $start, 'status' => $status->value]);
    $late = $leave('2026-11-09', LeaveRequestStatus::Pending);
    $leave('2026-11-08', LeaveRequestStatus::Accepted);
    $leave('2026-11-20', LeaveRequestStatus::Pending);
    LeaveRequest::factory()->create(['start_at' => '2026-11-02', 'end_at' => '2026-11-02', 'status' => LeaveRequestStatus::Pending->value]);

    expect((new AttentionCard)->rows($this->user)->pluck('key')->all())->toBe(['at-leave-'.$late->id]);
});

test('the attention card is in the Desk library for anyone linked to an employee', function () {
    expect(app(Panels::class)->availableTo($this->user))->toHaveKey('hr-attention')
        ->and(app(Panels::class)->availableTo(User::factory()->create(['employee_id' => null])))->not->toHaveKey('hr-attention');
});
