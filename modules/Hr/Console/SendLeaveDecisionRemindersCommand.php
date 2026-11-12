<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppNotification;
use App\Notifications\NotificationPayload;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Leave\LeaveDecisionAuthority;
use Modules\Hr\Support\Leave\LeaveNotificationService;
use Throwable;

/**
 * DEC-05 revision 2 (G-C-1): a pending request the manager has not answered
 * 3 days after it was sent, or 1 day before it starts (whichever comes
 * first), gets ONE inbox reminder to the direct manager, copied to HR in the
 * requester's city. HR that decides itself (no direct manager, or every one
 * on leave) is reminded to decide instead (G-C-18). The ledger claim is keyed
 * to the request, not the day, so a later run, a retry or a second worker
 * never reminds again.
 *
 * The sentences are hr_leave.notifications.reminder_{manager,hr,hr_decider}
 * and open the request's own page, as every other leave notification does.
 */
class SendLeaveDecisionRemindersCommand extends Command
{
    public const LEDGER_KIND = 'leave-decision-reminder';

    private const AFTER_DAYS = 3;

    protected $signature = 'leaves:send-decision-reminders';

    protected $description = 'Remind direct managers (copy to city HR) once about leave requests still awaiting their decision';

    public function handle(DeliveryLedger $ledger, LeaveNotificationService $notifications, LeaveDecisionAuthority $authority): int
    {
        $sent = 0;

        foreach ($this->dueRequests($ledger->today()) as $leave) {
            $day = $leave->created_at->timezone(config('app.timezone'))->startOfDay();
            $audience = $notifications->newRequestAudience($leave, $leave->leaveBalance?->contract?->employee?->account);

            foreach (['manager' => $audience['managers'], 'hr' => $audience['hr']] as $group => $users) {
                foreach ($users as $user) {
                    $type = $group === 'hr' && $this->decidesItself($authority, $user, $leave) ? 'hr_decider' : $group;

                    if ($ledger->claim(self::LEDGER_KIND, $user->id, [$leave->id], $day) === []) {
                        continue;
                    }

                    try {
                        $user->notify(new AppNotification($this->payload($leave, $type)));
                        $ledger->markDelivered(self::LEDGER_KIND, $user->id, [$leave->id], $day);
                        $sent++;
                    } catch (Throwable $e) {
                        $ledger->release(self::LEDGER_KIND, $user->id, [$leave->id], $day);
                        report($e);
                    }
                }
            }
        }

        $this->components->info("Sent {$sent} leave decision reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Pending, due, of an active employee, and never reminded before.
     *
     * @return Collection<int, LeaveRequest>
     */
    private function dueRequests(CarbonInterface $today): Collection
    {
        return LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Pending->value)
            ->where(fn (Builder $due) => $due
                ->where('created_at', '<=', now()->subDays(self::AFTER_DAYS))
                ->orWhereDate('start_at', '<=', $today->copy()->addDay()->toDateString()))
            ->whereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->where('is_active', true))
            ->whereNotIn('id', DB::table('notification_deliveries')->where('kind', self::LEDGER_KIND)->select('subject_id'))
            ->with([
                'leaveBalance.leaveType',
                'leaveBalance.contract.employee.account',
                'leaveBalance.contract.employee.city',
                'leaveBalance.contract.position.directManager.activeContracts.employee.account',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * May this HR account decide the request itself? Asked on the whole
     * account: the audience lists HR with a few columns only, and the
     * authority reads roles and permissions.
     */
    private function decidesItself(LeaveDecisionAuthority $authority, User $hr, LeaveRequest $leave): bool
    {
        $account = User::query()->find($hr->id);

        return $account !== null && $authority->basisFor($account, $leave) !== null;
    }

    private function payload(LeaveRequest $leave, string $type): NotificationPayload
    {
        $leaveType = $leave->leaveBalance?->leaveType;
        $employee = $leave->leaveBalance?->contract?->employee;

        return new NotificationPayload(
            kind: NotificationKind::Action,
            module: 'leaves',
            priority: NotificationPriority::Important,
            key: 'hr_leave.notifications.reminder_'.$type,
            params: [
                'employee' => ['localized' => ['en' => $employee?->nameForLocale('en') ?? '', 'ar' => $employee?->nameForLocale('ar') ?? '']],
                'type' => ['localized' => ['en' => (string) $leaveType?->name_en, 'ar' => (string) $leaveType?->name_ar]],
                'date' => ['date' => $leave->start_at?->toDateString()],
            ],
            subjectType: null,
            subjectId: null,
            link: ['route' => 'hr.leave.show', 'parameters' => ['leaveRequest' => $leave->id]],
        );
    }
}
