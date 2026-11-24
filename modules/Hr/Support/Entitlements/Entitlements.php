<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Entitlements;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeEntitlement;
use Modules\Hr\Models\Entitlement;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\PositionEntitlement;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Journeys\OffboardingJourneys;
use Modules\Hr\Support\Recruitment\ActivityLogger;

/**
 * The Permissions Library (meeting 19/11, track 5): who should hold which
 * professional item outside the system, and whether it was done.
 *
 * Nothing here grants a Spatie permission or touches a login: an item is a
 * record HR and the manager keep, the work itself happens elsewhere (the mail
 * provider, Telegram, the camera system...).
 *
 * Who may act on a person's items (entitlements.assign, and):
 *  - HR of the person's city and the administrators (EmployeePolicy::update),
 *  - the person's direct manager, under the manager role, for direct reports only
 *    (the same rule as Contract::scopeVisibleTo).
 */
final class Entitlements
{
    public function __construct(private readonly ActivityLogger $activity) {}

    // ── Defaults from a contract ──────────────────────────────────────

    /**
     * A contract just claimed a seat (EntitlementHooks): a new one, a draft
     * made real, one switched back on, or one moved to another position.
     *
     * Adds the defaults of its position that the person does not have yet,
     * and marks for review the defaults they hold that no longer apply.
     * Nothing is ever revoked here. A revoked default comes back as required
     * only when the person returns ($fresh and no other contract running):
     * a renewal keeps what HR or the manager decided.
     */
    public function syncForContract(Contract $contract, bool $fresh): void
    {
        $employeeId = (int) $contract->employee_id;

        if ($employeeId === 0 || ! $this->holdsSeat($contract)) {
            return;
        }

        $others = Contract::query()
            ->where('employee_id', $employeeId)
            ->whereKeyNot($contract->id)
            ->where('is_active', true)
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->whereNull('terminated_at')
            ->whereNull('termination_reason_id')
            ->get(['id', 'position_id', 'start_date', 'end_date']);

        // Running alongside this one (not a contract this one replaces).
        $alongside = $others->filter(fn (Contract $other): bool => $contract->start_date === null
            || $other->end_date === null
            || $other->end_date->toDateString() >= $contract->start_date->toDateString());

        $applicable = $this->defaultsFor(array_merge(
            [(int) $contract->position_id],
            $alongside->pluck('position_id')->map(fn (mixed $id): int => (int) $id)->all(),
        ));
        $returning = $fresh && $others->isEmpty();

        DB::transaction(function () use ($employeeId, $applicable, $returning): void {
            $rows = EmployeeEntitlement::query()->where('employee_id', $employeeId)->lockForUpdate()->get()->keyBy('entitlement_id');

            foreach ($applicable as $entitlementId) {
                $row = $rows->get($entitlementId);

                if ($row === null) {
                    EmployeeEntitlement::query()->create([
                        'employee_id' => $employeeId,
                        'entitlement_id' => $entitlementId,
                        'source' => EmployeeEntitlement::SOURCE_DEFAULT,
                        'state' => EmployeeEntitlement::STATE_REQUIRED,
                    ]);

                    continue;
                }

                $changes = [];

                if ($row->needs_review_at !== null) {
                    $changes['needs_review_at'] = null;
                }

                if ($returning && $row->state === EmployeeEntitlement::STATE_REVOKED) {
                    $changes += ['state' => EmployeeEntitlement::STATE_REQUIRED, 'source' => EmployeeEntitlement::SOURCE_DEFAULT];
                }

                if ($changes !== []) {
                    $row->update($changes);
                }
            }

            // Defaults of a position the person no longer holds: shown for review, never revoked here.
            $rows->except($applicable)
                ->filter(fn (EmployeeEntitlement $row): bool => $row->source === EmployeeEntitlement::SOURCE_DEFAULT
                    && $row->state !== EmployeeEntitlement::STATE_REVOKED
                    && $row->needs_review_at === null)
                ->each(fn (EmployeeEntitlement $row): bool => $row->update(['needs_review_at' => now()]));
        });
    }

    /**
     * The active library items that are defaults of these positions, directly
     * or through the position's job level.
     *
     * @param  array<int, int>  $positionIds
     * @return list<int>
     */
    public function defaultsFor(array $positionIds): array
    {
        $positionIds = array_values(array_unique(array_filter($positionIds)));

        if ($positionIds === []) {
            return [];
        }

        $jobLevelIds = Position::query()->whereKey($positionIds)->whereNotNull('job_level_id')->pluck('job_level_id')->all();

        return PositionEntitlement::query()
            ->whereHas('entitlement', fn (Builder $query): Builder => $query->active())
            ->where(fn (Builder $query): Builder => $query->whereIn('position_id', $positionIds)
                ->when($jobLevelIds !== [], fn (Builder $query): Builder => $query->orWhereIn('job_level_id', $jobLevelIds)))
            ->pluck('entitlement_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    // ── Who may act ───────────────────────────────────────────────────

    public function canAssign(?User $user, Employee $employee): bool
    {
        if ($user === null || ! $user->can('entitlements.assign')) {
            return false;
        }

        if (HrAccess::is($user, 'HR', 'super-admin')) {
            return Gate::forUser($user)->allows('update', $employee);
        }

        return HrAccess::isManager($user) && $this->isDirectManager($user, $employee);
    }

    /**
     * The manager runs (today) the position the person's current contract reports to.
     */
    public function isDirectManager(User $user, Employee $employee): bool
    {
        if (! $user->employee_id || (int) $user->employee_id === (int) $employee->id) {
            return false;
        }

        $managed = Contract::query()->activeNow()->where('employee_id', $user->employee_id)->pluck('position_id')->all();

        return $managed !== [] && Contract::query()->activeNow()
            ->where('employee_id', $employee->id)
            ->whereHas('position', fn (Builder $position): Builder => $position->whereIn('direct_manager_id', $managed))
            ->exists();
    }

    /**
     * The library page: HR roles and the administrators, with the permission.
     */
    public function canManageLibrary(?User $user): bool
    {
        return $user !== null
            && $user->can('entitlements.manage-library')
            && HrAccess::is($user, 'HR', 'super-admin');
    }

    /**
     * A job-level default applies in every city, so only those who are not
     * limited to cities (the administrators, national HR) set one.
     */
    public function canLinkJobLevels(?User $user): bool
    {
        return $this->canManageLibrary($user) && HrAccess::cityIds($user) === null;
    }

    // ── A person's items ──────────────────────────────────────────────

    /**
     * @return Collection<int, EmployeeEntitlement>
     */
    public function forEmployee(Employee $employee): Collection
    {
        return EmployeeEntitlement::query()
            ->where('employee_id', $employee->id)
            ->with(['entitlement.mailList', 'grantedBy', 'revokedBy'])
            ->get()
            ->sortBy(fn (EmployeeEntitlement $row): array => [
                $row->state === EmployeeEntitlement::STATE_REVOKED ? 1 : 0,
                $row->entitlement?->name,
            ])
            ->values();
    }

    /**
     * Add an item by hand (or bring back a revoked one). Returns false when it is already open.
     */
    public function add(Employee $employee, Entitlement $entitlement, User $actor, ?string $note = null): bool
    {
        $this->authorize($actor, $employee);

        return DB::transaction(function () use ($employee, $entitlement, $actor, $note): bool {
            $row = EmployeeEntitlement::query()
                ->where('employee_id', $employee->id)
                ->where('entitlement_id', $entitlement->id)
                ->lockForUpdate()
                ->first();

            if ($row !== null && $row->state !== EmployeeEntitlement::STATE_REVOKED) {
                return false;
            }

            $values = [
                'source' => EmployeeEntitlement::SOURCE_MANUAL,
                'state' => EmployeeEntitlement::STATE_REQUIRED,
                'needs_review_at' => null,
                'note' => filled($note) ? $note : $row?->note,
            ];

            if ($row !== null) {
                $row->update($values);
            } else {
                $row = EmployeeEntitlement::query()->create($values + ['employee_id' => $employee->id, 'entitlement_id' => $entitlement->id]);
            }

            $this->record($row, 'entitlement_added', $actor);

            return true;
        });
    }

    /**
     * Done outside the system.
     */
    public function markGranted(EmployeeEntitlement $row, User $actor): bool
    {
        return $this->change($row, $actor, 'entitlement_granted', fn (EmployeeEntitlement $locked): ?array => $locked->state === EmployeeEntitlement::STATE_REQUIRED
            ? ['state' => EmployeeEntitlement::STATE_GRANTED, 'granted_by' => $actor->id, 'granted_at' => now()]
            : null);
    }

    /**
     * Taken back (or no longer needed).
     */
    public function revoke(EmployeeEntitlement $row, User $actor): bool
    {
        $changed = $this->change($row, $actor, 'entitlement_revoked', fn (EmployeeEntitlement $locked): ?array => $locked->state !== EmployeeEntitlement::STATE_REVOKED
            ? ['state' => EmployeeEntitlement::STATE_REVOKED, 'revoked_by' => $actor->id, 'revoked_at' => now(), 'needs_review_at' => null]
            : null);

        // The last item taken back may be what the exit journey was waiting for.
        if ($changed) {
            $journeys = app(OffboardingJourneys::class);
            $journey = $journeys->latestFor((int) $row->employee_id);

            if ($journey !== null) {
                $journeys->settle($journey, $actor);
            }
        }

        return $changed;
    }

    /**
     * Reviewed after a position change: the person keeps it.
     */
    public function keep(EmployeeEntitlement $row, User $actor): bool
    {
        return $this->change($row, $actor, 'entitlement_kept', fn (EmployeeEntitlement $locked): ?array => $locked->needs_review_at !== null
            ? ['needs_review_at' => null]
            : null);
    }

    /**
     * Items still held (granted) that have to be taken back when the person
     * leaves: the exit journey lists them.
     *
     * @return Collection<int, EmployeeEntitlement>
     */
    public function outstanding(Employee $employee): Collection
    {
        return EmployeeEntitlement::query()
            ->where('employee_id', $employee->id)
            ->where('state', EmployeeEntitlement::STATE_GRANTED)
            ->with('entitlement')
            ->get();
    }

    public function hasAny(Employee $employee): bool
    {
        return EmployeeEntitlement::query()->where('employee_id', $employee->id)->exists();
    }

    // ── internals ─────────────────────────────────────────────────────

    private function holdsSeat(Contract $contract): bool
    {
        return (int) $contract->type !== ContractTypeEnum::Draft->value
            && ($contract->is_active === null || (bool) $contract->is_active)
            && ! $contract->isTerminated();
    }

    private function authorize(User $actor, Employee $employee): void
    {
        abort_unless($this->canAssign($actor, $employee), 403);
    }

    /**
     * @param  callable(EmployeeEntitlement): ?array<string, mixed>  $apply
     */
    private function change(EmployeeEntitlement $row, User $actor, string $event, callable $apply): bool
    {
        $employee = Employee::query()->findOrFail($row->employee_id);
        $this->authorize($actor, $employee);

        return DB::transaction(function () use ($row, $actor, $event, $apply): bool {
            $locked = EmployeeEntitlement::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $changes = $apply($locked);

            if ($changes === null) {
                return false;
            }

            $locked->update($changes);
            $this->record($locked, $event, $actor);

            return true;
        });
    }

    private function record(EmployeeEntitlement $row, string $event, User $actor): void
    {
        $this->activity->log($event, $row, $actor, [
            'employee_id' => $row->employee_id,
            'entitlement_id' => $row->entitlement_id,
            'state' => $row->state,
        ], 'entitlements');
    }
}
