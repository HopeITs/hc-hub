<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LeaveRequestFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * @property int $id
 * @property int $leave_balance_id
 * @property string $type
 * @property mixed $start_at
 * @property mixed $end_at
 * @property float|null $computed_hours
 * @property string|null $rule_version
 * @property bool $is_exceptional
 * @property string|null $reason
 * @property int|null $approved_by
 * @property string|null $decision_basis
 * @property int|null $decided_on_behalf_of
 * @property mixed|null $approved_at
 * @property bool $hr_approved
 * @property string|null $reject_reason
 * @property int $status
 * @property int|null $alternative_employee_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['leave_balance_id', 'type', 'start_at', 'end_at', 'reason', 'approved_by', 'approved_at', 'hr_approved', 'reject_reason', 'status', 'alternative_employee_id', 'computed_hours', 'rule_version', 'is_exceptional', 'decision_basis', 'decided_on_behalf_of'])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'approved_at' => 'datetime',
            'hr_approved' => 'boolean',
            'status' => 'integer',
            'computed_hours' => 'float',
            'is_exceptional' => 'boolean',
        ];
    }

    /**
     * How many days ahead an undecided request counts as starting soon (C3/C4).
     */
    public const STARTS_SOON_DAYS = 2;

    /**
     * Where an undecided request's first day stands (G-C-5): 'started' when it
     * is already behind today, 'soon' from today to today + STARTS_SOON_DAYS,
     * null otherwise. The inbox row and the home figure both read this, so the
     * "starts soon" label and the "N start within two days" count agree.
     */
    public static function startFlag(?DateTimeInterface $startAt): ?string
    {
        if ($startAt === null) {
            return null;
        }

        $day = CarbonImmutable::instance($startAt)->startOfDay();

        return match (true) {
            $day->lt(today()) => 'started',
            $day->lte(today()->addDays(self::STARTS_SOON_DAYS)) => 'soon',
            default => null,
        };
    }

    /**
     * @return BelongsTo<LeaveBalance, $this>
     */
    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    /**
     * @return HasOneThrough<LeaveType, LeaveBalance, $this>
     */
    public function leaveType(): HasOneThrough
    {
        return $this->hasOneThrough(LeaveType::class, LeaveBalance::class, 'id', 'id', 'leave_balance_id', 'type_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function alternativeEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'alternative_employee_id');
    }

    /**
     * HR's notes and objections, oldest first (C3).
     *
     * @return HasMany<LeaveRequestNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(LeaveRequestNote::class)->oldest();
    }

    /**
     * The manager HR decided on behalf of (C3), when it did.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function onBehalfOf(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'decided_on_behalf_of');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), LeaveRequestStatus::Pending->value);
    }

    /**
     * Requests in one status (a LeaveRequestStatus value).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, int $status): void
    {
        $query->where($query->qualifyColumn('status'), $status);
    }

    /**
     * Requests of employees living in one governorate (or any of several).
     *
     * @param  Builder<self>  $query
     * @param  int|list<int>|null  $cityIds
     */
    #[Scope]
    protected function forCity(Builder $query, int|array|null $cityIds): void
    {
        if ($cityIds !== null) {
            $query->whereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->whereIn('city_id', (array) $cityIds));
        }
    }

    /**
     * The requests $user may see and manage (the old scopeManageableBy):
     * national HR all of them; regional HR its governorates' plus its own
     * direct reports'; anyone else only the requests of the people whose
     * position reports to a position they hold today.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function manageableBy(Builder $query, User $user): void
    {
        $access = HrAccess::for($user);

        if ($access->isNational()) {
            return;
        }

        $managerPositionIds = $user->employee_id === null
            ? []
            : Contract::query()->activeNow()->where('employee_id', $user->employee_id)->pluck('position_id')->all();

        $directReports = fn (Builder $q) => $q->whereHas(
            'leaveBalance.contract.position',
            fn (Builder $position) => $position->whereIn('direct_manager_id', $managerPositionIds),
        );

        if ($access->isRegionalHr()) {
            $query->where(fn (Builder $scoped) => $scoped
                ->whereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->whereIn('city_id', $access->cityIds() ?? []))
                ->when($managerPositionIds !== [], fn (Builder $q) => $q->orWhere($directReports)));

            return;
        }

        if ($managerPositionIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $directReports($query);
    }

    /**
     * Requests filed on one employee's contracts.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofEmployee(Builder $query, int $employeeId): void
    {
        $query->whereHas('leaveBalance.contract', fn (Builder $contract) => $contract->where('employee_id', $employeeId));
    }

    /**
     * Leaves out a former employee's pending request: it is nobody's work to
     * decide (approval-source-contract.md §5, G-C-11). Their decided requests
     * stay, as history. The inbox, Team leaves and its counts all use this.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function exceptFormerStaffPending(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', '!=', LeaveRequestStatus::Pending->value)
            ->orWhereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->where('is_active', true)));
    }

    /**
     * Requests that have not ended yet.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereDate('start_at', '>=', today())
            ->orWhereDate('end_at', '>=', today()));
    }

    /**
     * Requests whose employee matches the search (see Employee::search()).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (trim((string) $term) !== '') {
            $query->whereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->search($term));
        }
    }
}
