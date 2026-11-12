<?php

use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Console\SendLeaveDecisionRemindersCommand;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;

/*
| `leaves:send-decision-reminders`: a pending leave request nobody decided 3
| days after it was sent, or 1 day before it starts, gets one reminder to the
| direct manager and a copy to HR in the requester's city.
*/

beforeEach(function () {
    $this->travelTo('2026-11-12 08:05:00');
    Notification::fake();

    $city = City::factory()->create();
    $managerPosition = Position::factory()->create();
    $this->manager = User::factory()->create();
    Contract::factory()->create(['employee_id' => $this->manager->employee_id, 'position_id' => $managerPosition->id, 'is_active' => true]);
    $this->hr = User::factory()->role('HR-'.strtoupper($city->slug))->create();

    $this->requester = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $city->id])->id]);
    $contract = Contract::factory()->create([
        'employee_id' => $this->requester->employee_id,
        'position_id' => Position::factory()->create(['direct_manager_id' => $managerPosition->id])->id,
        'is_active' => true,
    ]);
    $this->balance = LeaveBalance::factory()->for($contract)->create();
    $this->leave = fn (array $attributes = []): LeaveRequest => LeaveRequest::factory()->for($this->balance)->create($attributes + [
        'status' => LeaveRequestStatus::Pending->value,
        'start_at' => '2026-12-01',
        'end_at' => '2026-12-01',
        'created_at' => '2026-11-12 08:00:00',
    ]);
    $this->reminderKeys = fn (User $user): array => Notification::sent($user, AppNotification::class)
        ->filter(fn (AppNotification $notification): bool => str_starts_with($notification->payload->key, 'hr_leave.notifications.reminder_'))
        ->values()
        ->map(fn (AppNotification $notification): string => $notification->payload->key.'#'.$notification->payload->link['parameters']['leaveRequest'])
        ->all();
});

test('a request waiting three days reminds the manager and copies city HR, once', function () {
    $waiting = ($this->leave)(['created_at' => '2026-11-08 09:00:00']);

    $this->artisan(SendLeaveDecisionRemindersCommand::class)->assertSuccessful();
    $this->artisan(SendLeaveDecisionRemindersCommand::class)->assertSuccessful();

    expect(($this->reminderKeys)($this->manager))->toBe(["hr_leave.notifications.reminder_manager#{$waiting->id}"])
        ->and(($this->reminderKeys)($this->hr))->toBe(["hr_leave.notifications.reminder_hr#{$waiting->id}"])
        ->and(($this->reminderKeys)($this->requester))->toBe([]);
});

test('a request that starts tomorrow is reminded even when it was just sent', function () {
    $soon = ($this->leave)(['start_at' => '2026-11-13', 'end_at' => '2026-11-13']);

    $this->artisan(SendLeaveDecisionRemindersCommand::class)->assertSuccessful();

    expect(($this->reminderKeys)($this->manager))->toBe(["hr_leave.notifications.reminder_manager#{$soon->id}"]);
});

test('a fresh request, a decided one and a former employee\'s request are not reminded', function () {
    ($this->leave)();
    ($this->leave)(['created_at' => '2026-11-01 09:00:00', 'status' => LeaveRequestStatus::Accepted->value]);

    $formerContract = Contract::factory()->for(Employee::factory()->inactive())->create(['position_id' => $this->balance->contract->position_id]);
    LeaveRequest::factory()->for(LeaveBalance::factory()->for($formerContract))->create([
        'status' => LeaveRequestStatus::Pending->value,
        'created_at' => '2026-11-01 09:00:00',
    ]);

    $this->artisan(SendLeaveDecisionRemindersCommand::class)->assertSuccessful();

    expect(($this->reminderKeys)($this->manager))->toBe([])
        ->and(($this->reminderKeys)($this->hr))->toBe([]);
});

test('the reminder sentences exist in both languages with their placeholders', function (string $type) {
    foreach (['en', 'ar'] as $locale) {
        expect(__('hr_leave.notifications.reminder_'.$type, [], $locale))
            ->toContain(':employee')->toContain(':type')->toContain(':date');
    }
})->with(['manager', 'hr', 'hr_decider']);
