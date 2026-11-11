<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Leave;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppNotification;
use App\Notifications\NotificationPayload;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Throwable;

/**
 * Who hears about a leave request, and the hub notifications they get (the
 * old NewLeaveRequest, ActionLeaveRequest, HRSawLeaveRequest,
 * LeaveRequestObjected and ActionLeaveBalance). Each sentence lives in
 * hr_leave.notifications and links to the request's own page.
 *
 *  - a new request: the direct managers and the HR of the requester's city;
 *  - a decision: the requester, then that city's HR;
 *  - "seen by HR": the requester, then the managers;
 *  - an objection: the managers (to decide again), then the requester;
 *  - a balance change: the requester and the direct managers.
 *
 * The actor never notifies themself. A failed send is reported, never thrown:
 * it must not undo the write it describes.
 */
class LeaveNotificationService
{
    public function sendNewRequest(LeaveRequest $leaveRequest, ?User $actor = null): void
    {
        $audience = $this->newRequestAudience($leaveRequest, $actor);

        $this->send($audience['managers']->merge($audience['hr']), $leaveRequest, 'new_request', $actor, NotificationPriority::Important);
    }

    /**
     * Who is told about a new request, split by group. sendNewRequest() sends
     * to exactly these accounts, and LeaveRequestNextStep reads the same lists,
     * so the "who acts next" text can never drift from who was notified.
     *
     * @return array{managers: Collection<int, User>, hr: Collection<int, User>}
     */
    public function newRequestAudience(LeaveRequest $leaveRequest, ?User $actor = null): array
    {
        $leaveRequest = $this->loadContext($leaveRequest);

        return [
            'managers' => $this->unique($this->managerRecipients($leaveRequest), [$actor?->id]),
            'hr' => $this->unique($this->hrRecipients($leaveRequest), [$actor?->id]),
        ];
    }

    public function sendDecision(LeaveRequest $leaveRequest, ?User $actor = null): void
    {
        $leaveRequest = $this->loadContext($leaveRequest);
        $accepted = (int) $leaveRequest->status === LeaveRequestStatus::Accepted->value;
        $employees = $this->unique(collect([$this->requesterAccount($leaveRequest)]), [$actor?->id]);

        $this->send($employees, $leaveRequest, $accepted ? 'accepted_you' : 'rejected_you', $actor, NotificationPriority::Important);
        $this->send(
            $this->unique($this->hrRecipients($leaveRequest), [$actor?->id, ...$employees->pluck('id')->all()]),
            $leaveRequest,
            $accepted ? 'accepted' : 'rejected',
            $actor,
            NotificationPriority::Info,
        );
    }

    public function sendHrReview(LeaveRequest $leaveRequest, ?User $actor = null): void
    {
        $leaveRequest = $this->loadContext($leaveRequest);
        $employees = $this->unique(collect([$this->requesterAccount($leaveRequest)]), [$actor?->id]);

        $this->send($employees, $leaveRequest, 'seen_you', $actor, NotificationPriority::Info);
        $this->send(
            $this->unique($this->managerRecipients($leaveRequest), [$actor?->id, ...$employees->pluck('id')->all()]),
            $leaveRequest,
            'seen',
            $actor,
            NotificationPriority::Info,
        );
    }

    /**
     * HR objected (C3 G3): the direct manager is asked to decide again and the
     * requester is told why the request is pending once more.
     */
    public function sendObjection(LeaveRequest $leaveRequest, ?User $actor, string $note): void
    {
        $leaveRequest = $this->loadContext($leaveRequest);
        $managers = $this->unique($this->managerRecipients($leaveRequest), [$actor?->id]);

        $this->send($managers, $leaveRequest, 'objected', $actor, NotificationPriority::Important, ['note' => $note]);
        $this->send(
            $this->unique(collect([$this->requesterAccount($leaveRequest)]), [$actor?->id, ...$managers->pluck('id')->all()]),
            $leaveRequest,
            'objected_you',
            $actor,
            NotificationPriority::Important,
            ['note' => $note],
        );
    }

    /**
     * HR added to or took from an annual balance (the old ActionLeaveBalance).
     */
    public function sendBalanceChange(LeaveBalance $balance, ?User $actor, string $operation, string $amount, ?string $note): void
    {
        $contract = Contract::query()
            ->with([
                'employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar',
                'employee.account:id,employee_id',
                'position:id,direct_manager_id',
                'position.directManager:id',
                'position.directManager.activeContracts:id,position_id,employee_id',
                'position.directManager.activeContracts.employee:id,is_active',
                'position.directManager.activeContracts.employee.account:id,employee_id',
            ])
            ->find($balance->contract_id);

        if ($contract === null) {
            return;
        }

        $params = [
            'employee' => $this->localizedName($contract->employee),
            'amount' => $amount,
            'note' => (string) $note,
        ];
        $key = $operation === 'sum' ? 'balance_added' : 'balance_taken';
        $requester = $contract->employee?->account;
        $managers = collect($contract->position?->directManager?->activeContracts ?? [])
            ->map(fn (Contract $held) => $held->employee?->is_active ? $held->employee->account : null);

        $this->dispatch($this->unique(collect([$requester]), [$actor?->id]), $key.'_you', $params, $actor, ['route' => 'hr.leave.index']);
        $this->dispatch($this->unique($managers, [$actor?->id, $requester?->id]), $key, $params, $actor, ['route' => 'hr.leave.team']);
    }

    private function loadContext(LeaveRequest $leaveRequest): LeaveRequest
    {
        return $leaveRequest->loadMissing([
            'leaveBalance.leaveType:id,name_en,name_ar',
            'leaveBalance.contract.employee.account',
            'leaveBalance.contract.employee.city',
            'leaveBalance.contract.position.directManager.activeContracts.employee.account',
        ]);
    }

    private function requesterAccount(LeaveRequest $leaveRequest): ?User
    {
        return $leaveRequest->leaveBalance?->contract?->employee?->account;
    }

    /**
     * Every active employee holding the direct-manager position (the
     * technical manager no longer exists, C3 G2).
     *
     * @return Collection<int, User|null>
     */
    private function managerRecipients(LeaveRequest $leaveRequest): Collection
    {
        $position = $leaveRequest->leaveBalance?->contract?->position;

        return collect($position?->directManager?->activeContracts ?? [])
            ->map(fn (Contract $contract) => $contract->employee)
            ->filter(fn (?Employee $employee) => $employee?->is_active && $employee->account !== null)
            ->map(fn (Employee $employee) => $employee->account)
            ->values();
    }

    /**
     * Every account holding an HR role of the requester's governorate (HR-ALP,
     * or the regional role that covers it through access-control.hr_role_cities).
     *
     * @return Collection<int, User>
     */
    private function hrRecipients(LeaveRequest $leaveRequest): Collection
    {
        $slug = strtoupper(trim((string) $leaveRequest->leaveBalance?->contract?->employee?->city?->slug));

        if ($slug === '') {
            return collect();
        }

        $roles = collect(config('access-control.hr_role_cities', []))
            ->filter(fn (mixed $slugs) => is_array($slugs) && in_array($slug, $slugs, true))
            ->keys()
            ->map(fn (mixed $name): string => (string) $name)
            ->all();

        if ($roles === []) {
            $roles = ['HR-'.$slug];
        }

        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->whereHas('employee', fn ($query) => $query->where('is_active', true))
            ->get(['id', 'employee_id', 'locale']);
    }

    /**
     * @param  Collection<int, User|null>  $users
     * @param  array<int, int|null>  $excludedIds
     * @return Collection<int, User>
     */
    private function unique(Collection $users, array $excludedIds = []): Collection
    {
        $excludedIds = array_values(array_filter($excludedIds, fn ($id) => $id !== null));

        return $users
            ->filter(fn ($user) => $user instanceof User)
            ->reject(fn (User $user) => in_array($user->id, $excludedIds, true))
            ->unique('id')
            ->values();
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $extra
     */
    private function send(Collection $recipients, LeaveRequest $leaveRequest, string $event, ?User $actor, NotificationPriority $priority, array $extra = []): void
    {
        $type = $leaveRequest->leaveBalance?->leaveType;
        $params = [
            'employee' => $this->localizedName($leaveRequest->leaveBalance?->contract?->employee),
            'type' => ['localized' => ['en' => (string) $type?->name_en, 'ar' => (string) $type?->name_ar]],
            'reason' => (string) $leaveRequest->reject_reason,
        ] + $extra;

        $this->dispatch($recipients, $event, $params, $actor, ['route' => 'hr.leave.show', 'parameters' => ['leaveRequest' => $leaveRequest->id]], $priority);
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $params
     * @param  array{route?: string, parameters?: array<string, mixed>}  $link
     */
    private function dispatch(Collection $recipients, string $event, array $params, ?User $actor, array $link, NotificationPriority $priority = NotificationPriority::Info): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = new NotificationPayload(
            kind: NotificationKind::Action,
            module: 'leaves',
            priority: $priority,
            key: 'hr_leave.notifications.'.$event,
            params: $params,
            subjectType: null,
            subjectId: null,
            actorId: $actor?->id,
            link: $link,
        );

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new AppNotification($payload));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * @return array{localized: array{en: string, ar: string}}
     */
    private function localizedName(?Employee $employee): array
    {
        return ['localized' => [
            'en' => $employee?->nameForLocale('en') ?? '',
            'ar' => $employee?->nameForLocale('ar') ?? '',
        ]];
    }
}
