<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Inbox;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Models\ProfileChangeRequest;
use Modules\Hr\Models\RenewalJourney;
use Modules\Hr\Support\Desk\HrViewer;
use WeakMap;

/**
 * The HR work inbox: everything waiting on one person, read from the records
 * themselves (nothing is stored for the inbox).
 *
 * Sources, as in the old HR app:
 *  - leave requests to decide: a pending request of someone who reports to
 *    one of the viewer's positions, or (HR with leaves.decide) of anyone in
 *    the viewer's governorates;
 *  - leave requests to review: HR with leaves.review sees decided requests
 *    HR has not confirmed yet, except the ones they decided themselves and
 *    their own requests;
 *  - checklist items to deliver or to take back (the viewer is named on them);
 *  - returned items to close and profile changes to approve (HR with
 *    employees.update, in their governorates);
 *  - open onboarding, renewal and exit journeys (same HR rule).
 *
 * A former employee's pending leave is nobody's work and is left out. Each
 * row opens the page that owns the record; the decision itself is made there.
 */
final class WorkInbox
{
    /**
     * The most rows read from one source.
     */
    public const int PerSource = 100;

    /**
     * How soon a leave must start to be urgent.
     */
    public const int UrgentDays = 2;

    /**
     * @var WeakMap<User, array{rows: Collection<int, array<string, mixed>>, counts: array<string, int>}>|null
     */
    private static ?WeakMap $snapshots = null;

    /**
     * The viewer's inbox, read once per request.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, counts: array{all: int, decide: int, review: int, checklist: int, profile: int, journey: int}}
     */
    public function for(User $user): array
    {
        self::$snapshots ??= new WeakMap;

        return self::$snapshots[$user] ??= $this->snapshot($user);
    }

    public function count(User $user): int
    {
        return $this->for($user)['counts']['all'];
    }

    /**
     * Whether anything can ever reach this person's inbox.
     */
    public function hasSources(User $user): bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        $viewer = HrViewer::for($user);

        return $user->canAny(['leaves.decide', 'leaves.review', 'checklists.deliver', 'checklists.receive'])
            || ($this->isHr($viewer) && $user->can('employees.update'))
            || $viewer->reportIds() !== [];
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, counts: array{all: int, decide: int, review: int, checklist: int, profile: int, journey: int}}
     */
    private function snapshot(User $user): array
    {
        $viewer = HrViewer::for($user);
        $decide = $this->leaveDecisions($user, $viewer);
        $review = $this->leaveReviews($user, $viewer);
        $checklist = $this->checklistItems($user, $viewer);
        $profile = $this->profileChanges($user, $viewer);
        $journey = $this->journeys($user, $viewer);

        return [
            'rows' => collect()->merge($decide)->merge($review)->merge($checklist)->merge($profile)->merge($journey)
                ->sortBy(fn (array $row): int => $row['since']?->getTimestamp() ?? PHP_INT_MAX)
                ->values(),
            'counts' => [
                'all' => $decide->count() + $review->count() + $checklist->count() + $profile->count() + $journey->count(),
                'decide' => $decide->count(),
                'review' => $review->count(),
                'checklist' => $checklist->count(),
                'profile' => $profile->count(),
                'journey' => $journey->count(),
            ],
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function leaveDecisions(User $user, HrViewer $viewer): Collection
    {
        $reports = $viewer->reportIds();
        $asHr = $this->isHr($viewer) && $user->can('leaves.decide');

        if ($reports === [] && ! $asHr) {
            return new Collection;
        }

        $cities = $viewer->cityScope();

        return $this->leaveQuery()
            ->where('status', LeaveRequestStatus::Pending->value)
            ->whereHas('leaveBalance.contract.employee', function (Builder $employee) use ($reports, $asHr, $cities, $viewer): void {
                $employee->where('is_active', true)
                    ->where('employees.id', '!=', (int) $viewer->employeeId())
                    ->where(function (Builder $reach) use ($reports, $asHr, $cities): void {
                        $reach->whereIn('employees.id', $reports);

                        if ($asHr) {
                            $reach->orWhere(fn (Builder $inCity) => $cities === null ? $inCity->whereRaw('1 = 1') : $inCity->whereIn('city_id', $cities));
                        }
                    });
            })
            ->get()
            ->map(fn (LeaveRequest $leave): array => $this->leaveRow($leave, 'decide'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function leaveReviews(User $user, HrViewer $viewer): Collection
    {
        if (! $this->isHr($viewer) || ! $user->can('leaves.review')) {
            return new Collection;
        }

        $cities = $viewer->cityScope();
        $self = $viewer->employeeId();

        return $this->leaveQuery()
            ->where('status', '!=', LeaveRequestStatus::Pending->value)
            ->where('hr_approved', false)
            ->when($self !== null, fn (Builder $query) => $query->where(fn (Builder $by) => $by->whereNull('approved_by')->orWhere('approved_by', '!=', $self)))
            ->when($self !== null, fn (Builder $query) => $query->whereDoesntHave('leaveBalance.contract', fn (Builder $contract) => $contract->where('employee_id', $self)))
            ->whereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->where('is_active', true)->when($cities !== null, fn (Builder $inCity) => $inCity->whereIn('city_id', $cities)))
            ->get()
            ->map(fn (LeaveRequest $leave): array => $this->leaveRow($leave, 'review'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function checklistItems(User $user, HrViewer $viewer): Collection
    {
        $self = $viewer->employeeId();
        $rows = new Collection;
        $query = fn (): Builder => Checklist::query()
            ->select(['id', 'item_id', 'employee_id', 'delivered_id', 'receiver_id', 'delivered_status', 'returned_status', 'returned_at', 'created_at', 'updated_at'])
            ->whereHas('employee')
            ->with(['item:id,name', 'employee:'.implode(',', [...Employee::NameColumns, 'city_id', 'is_active'])])
            ->orderBy('created_at')
            ->limit(self::PerSource);

        if ($self !== null && $user->can('checklists.deliver')) {
            $rows = $rows->merge($query()->where('delivered_id', $self)->where('delivered_status', 'pending')->get()
                ->map(fn (Checklist $item): array => $this->checklistRow($item, 'deliver', $item->created_at)));
        }

        if ($self !== null && $user->can('checklists.receive')) {
            $rows = $rows->merge($query()->where('receiver_id', $self)->where('returned_status', 'pending')->get()
                ->map(fn (Checklist $item): array => $this->checklistRow($item, 'receive', $item->returned_at ?? $item->updated_at)));
        }

        if ($this->isHr($viewer) && $user->can('employees.update')) {
            $cities = $viewer->cityScope();
            $rows = $rows->merge($query()->where('returned_status', 'received')
                ->whereHas('employee', fn (Builder $employee) => $employee->when($cities !== null, fn (Builder $inCity) => $inCity->whereIn('city_id', $cities)))
                ->get()
                ->map(fn (Checklist $item): array => $this->checklistRow($item, 'finalize', $item->returned_at ?? $item->updated_at)));
        }

        return $rows;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function profileChanges(User $user, HrViewer $viewer): Collection
    {
        if (! $this->isHr($viewer) || ! $user->can('employees.update')) {
            return new Collection;
        }

        $cities = $viewer->cityScope();

        return ProfileChangeRequest::query()->pending()
            ->select(['id', 'employee_id', 'section', 'status', 'created_at'])
            ->whereHas('employee', fn (Builder $employee) => $employee->where('is_active', true)->when($cities !== null, fn (Builder $inCity) => $inCity->whereIn('city_id', $cities)))
            ->with('employee:'.implode(',', [...Employee::NameColumns, 'city_id']))
            ->orderBy('created_at')
            ->limit(self::PerSource)
            ->get()
            ->map(fn (ProfileChangeRequest $change): array => $this->row(
                "profile:{$change->id}:approve", 'profile', 'approve', $change->employee,
                (string) __('hr_communications.inbox.profile_section', ['section' => (string) $change->section]),
                $change->created_at, false, $this->link('hr.employees.show', $change->employee_id),
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function journeys(User $user, HrViewer $viewer): Collection
    {
        if (! $this->isHr($viewer) || ! $user->can('employees.update')) {
            return new Collection;
        }

        $cities = $viewer->cityScope();
        $inReach = fn (Builder $employee) => $employee->when($cities !== null, fn (Builder $inCity) => $inCity->whereIn('city_id', $cities));
        $person = 'employee:'.implode(',', [...Employee::NameColumns, 'city_id']);
        $rows = new Collection;

        foreach ([
            'onboarding' => OnboardingJourney::query()->select(['id', 'employee_id', 'started_at', 'orientation_due_on', 'completed_at'])->whereNull('completed_at'),
            'renewal' => RenewalJourney::query()->select(['id', 'employee_id', 'started_at', 'decision', 'completed_at'])->whereNull('completed_at'),
            'exit' => OffboardingJourney::query()->select(['id', 'employee_id', 'started_at', 'completed_at'])->whereNull('completed_at'),
        ] as $kind => $query) {
            $rows = $rows->merge($query->whereHas('employee', $inReach)->with($person)->orderBy('started_at')->limit(self::PerSource)->get()
                ->map(fn ($journey): array => $this->row(
                    "journey:{$kind}:{$journey->id}", 'journey', $kind, $journey->employee,
                    (string) __('hr_communications.inbox.journey.'.$kind),
                    $journey->started_at, false, $this->link('hr.employees.show', $journey->employee_id),
                )));
        }

        return $rows;
    }

    /**
     * @return Builder<LeaveRequest>
     */
    private function leaveQuery(): Builder
    {
        return LeaveRequest::query()
            ->select(['id', 'leave_balance_id', 'type', 'start_at', 'end_at', 'status', 'hr_approved', 'approved_by', 'approved_at', 'created_at', 'updated_at'])
            ->with([
                'leaveBalance:id,contract_id,type_id',
                'leaveBalance.leaveType:id,name_en,name_ar',
                'leaveBalance.contract:id,employee_id',
                'leaveBalance.contract.employee:'.implode(',', [...Employee::NameColumns, 'city_id']),
            ])
            ->orderBy('created_at')
            ->limit(self::PerSource);
    }

    /**
     * @return array<string, mixed>
     */
    private function leaveRow(LeaveRequest $leave, string $action): array
    {
        $urgent = $action === 'decide' && $leave->start_at !== null && $leave->start_at->lte(now()->addDays(self::UrgentDays)->endOfDay());

        return [
            ...$this->row(
                "leave:{$leave->id}:{$action}", 'leave', $action, $leave->leaveBalance?->contract?->employee,
                (string) ($leave->leaveBalance?->leaveType?->name ?? ''),
                $action === 'review' ? ($leave->approved_at ?? $leave->updated_at) : $leave->created_at,
                $urgent, $this->link('hr.leave.requests.index', ['open' => $leave->id]),
            ),
            'from' => $leave->start_at,
            'to' => $leave->end_at,
            'started' => $action === 'decide' && $leave->start_at !== null && $leave->start_at->isPast(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checklistRow(Checklist $item, string $action, ?CarbonInterface $since): array
    {
        return $this->row("checklist:{$item->id}:{$action}", 'checklist', $action, $item->employee, (string) ($item->item?->name ?? ''), $since, false, $this->link('hr.checklists.index'));
    }

    /**
     * @return array{key: string, source: string, action: string, employee_id: int|null, name: string, words: string, what: string, city: string, since: CarbonInterface|null, urgent: bool, from: CarbonInterface|null, to: CarbonInterface|null, started: bool, href: string}
     */
    private function row(string $key, string $source, string $action, ?Employee $employee, string $what, ?CarbonInterface $since, bool $urgent, string $href): array
    {
        return [
            'key' => $key,
            'source' => $source,
            'action' => $action,
            'employee_id' => $employee === null ? null : (int) $employee->id,
            'name' => (string) ($employee?->name ?? '—'),
            'words' => (string) $employee?->secondary_name,
            'what' => $what,
            'city' => City::nameOf($employee?->city_id === null ? null : (int) $employee->city_id),
            'since' => $since,
            'urgent' => $urgent,
            'from' => null,
            'to' => null,
            'started' => false,
            'href' => $href,
        ];
    }

    private function isHr(HrViewer $viewer): bool
    {
        return $viewer->isHr() || $viewer->user->isAdministrator();
    }

    private function link(string $route, mixed $parameters = []): string
    {
        return Route::has($route) ? route($route, $parameters) : '';
    }
}
