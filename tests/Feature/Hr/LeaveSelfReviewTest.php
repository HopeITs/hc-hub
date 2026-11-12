<?php

use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Inbox\WorkInbox;
use Spatie\Permission\Models\Permission;

/*
| "Seen by HR" is a second pair of eyes on a decided leave request, so an HR
| reviewer never reviews their own request: the policy refuses it and it does
| not reach their work inbox. Everyone else's decided requests still do.
*/

beforeEach(function () {
    $this->reviewerEmployee = Employee::factory()->create();
    $this->reviewer = User::factory()->create(['employee_id' => $this->reviewerEmployee->id]);
    $this->reviewer->assignRole(Role::findOrCreate('HR-SYR', 'web'));
    $this->reviewer->givePermissionTo(Permission::findOrCreate('leaves.review', 'web'));
    $this->reviewer = $this->reviewer->fresh();

    $manager = Employee::factory()->create();

    $this->decidedLeaveOf = function (Employee $employee) use ($manager): LeaveRequest {
        $balance = LeaveBalance::factory()->for(Contract::factory()->for($employee)->create(['is_active' => true]))->create();

        return LeaveRequest::factory()->for($balance)->create([
            'status' => LeaveRequestStatus::Accepted->value,
            'approved_by' => $manager->id,
            'approved_at' => now(),
        ])->fresh()->load('leaveBalance.contract');
    };
});

test('an HR reviewer cannot mark their own leave request as seen', function () {
    $own = ($this->decidedLeaveOf)($this->reviewerEmployee);

    expect(Gate::forUser($this->reviewer)->allows('review', $own))->toBeFalse();
});

test('their own decided request does not reach their work inbox', function () {
    $own = ($this->decidedLeaveOf)($this->reviewerEmployee);

    $inbox = app(WorkInbox::class)->for($this->reviewer);

    expect($inbox['counts']['review'])->toBe(0)
        ->and($inbox['rows']->pluck('key')->all())->not->toContain("leave:{$own->id}:review");
});

test('another employee\'s decided request is still theirs to review and sits in their inbox', function () {
    ($this->decidedLeaveOf)($this->reviewerEmployee);
    $other = ($this->decidedLeaveOf)(Employee::factory()->create());

    expect(Gate::forUser($this->reviewer)->allows('review', $other))->toBeTrue()
        ->and(app(WorkInbox::class)->for($this->reviewer)['counts']['review'])->toBe(1);
});
