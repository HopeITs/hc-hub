<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Hr\Casts\EncryptedSalary;
use Modules\Hr\Enums\ContractAmendmentStatus;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Observers\HrStatisticsObserver;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * @property int $id
 * @property int $employee_id
 * @property int $position_id
 * @property int $contract_type_id
 * @property int $type
 * @property int $place
 * @property int $schedule_mode
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property int|null $termination_reason_id
 * @property CarbonImmutable|null $terminated_at
 * @property string|null $reason_of_termination
 * @property string|null $salary
 * @property int|null $currency
 * @property int $salary_per
 * @property int $pay_mode
 * @property int|null $session_duration
 * @property string|null $work_hours_from
 * @property string|null $work_hours_to
 * @property string|null $work_hours_2_from
 * @property string|null $work_hours_2_to
 * @property int|null $work_day_from
 * @property int|null $work_day_to
 * @property bool $show_days_per_week
 * @property bool $show_hours_per_week
 * @property bool $show_hours_per_day
 * @property bool $show_day_range
 * @property int|null $flex_days_per_week
 * @property int|null $flex_hours_per_week
 * @property int|null $flex_hours_per_month
 * @property bool $flex_varies_by_requirements
 * @property bool $flex_coordinated_with_manager
 * @property string|null $flex_custom_en
 * @property string|null $flex_custom_ar
 * @property string|null $work_days_en
 * @property string|null $work_days_ar
 * @property int|null $weekly_hours
 * @property bool $is_active
 * @property bool $auto_renew
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(HrStatisticsObserver::class)]
#[Fillable(['employee_id', 'position_id', 'contract_type_id', 'type', 'place', 'start_date', 'end_date', 'termination_reason_id', 'terminated_at', 'reason_of_termination', 'salary', 'currency', 'salary_per', 'work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to', 'work_days_en', 'work_days_ar', 'weekly_hours', 'schedule_mode', 'work_day_from', 'work_day_to', 'show_days_per_week', 'show_hours_per_week', 'show_hours_per_day', 'show_day_range', 'flex_days_per_week', 'flex_hours_per_week', 'flex_hours_per_month', 'flex_varies_by_requirements', 'flex_coordinated_with_manager', 'flex_custom_en', 'flex_custom_ar', 'pay_mode', 'session_duration', 'is_active', 'auto_renew'])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    /**
     * The person at the top of the organisation: the holder of the active
     * position titled notifications.top_level_position_title that has no
     * direct manager (the deputy has none either; the title singles the top
     * out). They sit above the program managers in the notification chain.
     *
     * @return list<int>
     */
    public static function topLevelEmployeeIds(): array
    {
        return array_values(static::query()
            ->join('positions', 'positions.id', '=', 'contracts.position_id')
            ->where('contracts.is_active', true)
            ->where('positions.is_active', true)
            ->whereNull('positions.direct_manager_id')
            ->where('positions.title_en', (string) config('notifications.top_level_position_title'))
            ->distinct()
            ->pluck('contracts.employee_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'terminated_at' => 'date',
            'is_active' => 'boolean',
            'weekly_hours' => 'integer',
            'type' => 'integer',
            'place' => 'integer',
            'schedule_mode' => 'integer',
            'work_day_from' => 'integer',
            'work_day_to' => 'integer',
            'show_days_per_week' => 'boolean',
            'show_hours_per_week' => 'boolean',
            'show_hours_per_day' => 'boolean',
            'show_day_range' => 'boolean',
            'flex_days_per_week' => 'integer',
            'flex_hours_per_week' => 'integer',
            'flex_hours_per_month' => 'integer',
            'flex_varies_by_requirements' => 'boolean',
            'flex_coordinated_with_manager' => 'boolean',
            'pay_mode' => 'integer',
            'session_duration' => 'integer',
            'currency' => 'integer',
            'salary' => EncryptedSalary::class,
            'auto_renew' => 'boolean',
        ];
    }

    /**
     * The contract this row is being created as the successor of.
     *
     * Transient and never persisted — it exists only between building the model
     * and ContractObserver::creating() reading it. It MUST stay a declared
     * property: assigned dynamically, Eloquent would treat it as an attribute
     * and try to insert a `renewal_source_id` column that does not exist.
     */
    public ?int $renewalSourceId = null;

    //    protected function work_days(): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn() => $this->getTranslatableAttribute('work_days'),
    //            set: fn($value) => $this->setTranslatableAttribute('work_days', $value)
    //        );
    //    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveNow(Builder $query): Builder
    {
        return $query
            ->where($query->qualifyColumn('is_active'), true)
            ->whereDate($query->qualifyColumn('start_date'), '<=', now())
            ->whereDate($query->qualifyColumn('end_date'), '>=', now());
    }

    /**
     * Restrict contracts to assignments belonging to one center.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAtCenter(Builder $query, int $centerId): Builder
    {
        return $query->whereHas(
            'position',
            fn (Builder $position): Builder => $position->where('center_id', $centerId),
        );
    }

    /**
     * A named contracts view (B4 presets): `active` (default), `ending-soon`,
     * `ended`, `not-renewed`, `draft`. Shared by the contracts table and the home page's
     * "ending soon" figure (C4), so the count and the list it opens agree.
     * It only narrows; combine it with visibleTo() for the role's scope.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInView(Builder $query, string $view, int $endingSoonDays = 30): Builder
    {
        $today = today();
        $notDraft = fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->whereNull('contracts.type')->orWhere('contracts.type', '!=', ContractTypeEnum::Draft->value));
        $running = function (Builder $q) use ($notDraft, $today) {
            $notDraft($q);
            $q->where('contracts.is_active', true)
                ->whereNull('contracts.terminated_at')
                ->whereNull('contracts.termination_reason_id')
                ->where(fn (Builder $w) => $w->whereNull('contracts.end_date')->orWhereDate('contracts.end_date', '>=', $today));
        };

        match ($view) {
            'ending-soon' => $query->where(function (Builder $q) use ($running, $today, $endingSoonDays) {
                $running($q);
                $q->whereNotNull('contracts.end_date')
                    ->whereDate('contracts.end_date', '<=', $today->copy()->addDays($endingSoonDays));
            }),
            'ended' => $query->where(function (Builder $q) use ($notDraft, $today) {
                $notDraft($q);
                $q->where(fn (Builder $w) => $w
                    ->whereNotNull('contracts.terminated_at')
                    ->orWhereNotNull('contracts.termination_reason_id')
                    ->orWhere('contracts.is_active', false)
                    ->orWhereDate('contracts.end_date', '<', $today));
            }),
            // Ended (not ended early) within the last $endingSoonDays days, the
            // person is still on staff, and nothing newer was made for them:
            // the home page's late band (prompt 7) counts this very view.
            'not-renewed' => $query->where(function (Builder $q) use ($notDraft, $today, $endingSoonDays) {
                $notDraft($q);
                $q->whereNull('contracts.terminated_at')
                    ->whereNull('contracts.termination_reason_id')
                    ->whereNotNull('contracts.end_date')
                    ->whereDate('contracts.end_date', '<', $today)
                    ->whereDate('contracts.end_date', '>=', $today->copy()->subDays($endingSoonDays))
                    ->whereHas('employee', fn (Builder $e) => $e->where('is_active', true))
                    ->whereNotExists(fn ($later) => $later->selectRaw('1')
                        ->from('contracts as later')
                        ->whereColumn('later.employee_id', 'contracts.employee_id')
                        ->whereColumn('later.id', '!=', 'contracts.id')
                        ->whereColumn('later.start_date', '>=', 'contracts.start_date'));
            }),
            'draft' => $query->where('contracts.type', ContractTypeEnum::Draft->value),
            default => $query->where($running),
        };

        return $query;
    }

    /**
     * Restrict the query to exactly one contract per employee: the most recent
     * one by start_date, ties broken by the higher id.
     *
     * WHY: every dashboard statistic needs "the employee's current contract".
     * The previous approach was to load all employees with all their contracts
     * eager-loaded and then call ->sortByDesc('start_date')->first() in PHP,
     * which pulls the entire employees + contracts + positions tables into
     * memory to produce what is ultimately a GROUP BY COUNT.
     *
     * Do NOT replace this with `->with(['contracts' => fn ($q) => $q->limit(1)])`.
     * A limit inside an eager load applies to the combined result set, not per
     * parent row, so it returns one contract for the entire page rather than
     * one per employee — that exact bug was live in JobLevelEmployeeStatistics.
     *
     * Implemented as NOT EXISTS ("no newer contract exists for this employee")
     * rather than a window function, because the suite runs on SQLite in-memory
     * while dev/prod run MySQL, and NOT EXISTS behaves identically on both.
     *
     * @param  Closure|null  $constrain  Optional filter applied to BOTH the outer query
     *                                   and the inner "is there a newer one" subquery.
     *                                   Passing it only to the outer query would ask
     *                                   "latest contract overall, that happens to be
     *                                   active" instead of "latest active contract" —
     *                                   which silently drops employees whose newest
     *                                   contract is expired. Callers that need an
     *                                   active-only view must go through this argument.
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLatestPerEmployee(Builder $query, ?Closure $constrain = null): Builder
    {
        if ($constrain) {
            $constrain($query);
        }

        return $query->whereNotExists(function ($sub) use ($constrain) {
            $sub->selectRaw('1')
                ->from('contracts as newer')
                ->whereColumn('newer.employee_id', 'contracts.employee_id')
                ->where(function ($tie) {
                    $tie->whereColumn('newer.start_date', '>', 'contracts.start_date')
                        ->orWhere(function ($sameDate) {
                            $sameDate->whereColumn('newer.start_date', '=', 'contracts.start_date')
                                ->whereColumn('newer.id', '>', 'contracts.id');
                        });
                });

            if ($constrain) {
                // Re-apply against the aliased table so the comparison stays
                // within the same population as the outer query.
                $constrain($sub, 'newer');
            }
        });
    }

    /**
     * The contracts a user may see: a manager sees the people who report to
     * the positions they hold, a regional HR role sees its cities, everyone
     * else who holds contracts.view sees all.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (HrAccess::is($user, 'manager')) {
            $managedPositionIds = $user->employee_id
                ? static::query()->activeNow()->where('employee_id', $user->employee_id)->pluck('position_id')->all()
                : [];

            if ($managedPositionIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('position', fn (Builder $position): Builder => $position->whereIn('direct_manager_id', $managedPositionIds));
        }

        $cityIds = HrAccess::cityIds($user);

        return $query->when(
            $cityIds !== null,
            fn (Builder $q): Builder => $q->whereHas('employee', fn (Builder $e): Builder => $e->whereIn('city_id', $cityIds)),
        );
    }

    /**
     * @return BelongsTo<ContractType, $this>
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    /**
     * @return BelongsTo<TerminationReason, $this>
     */
    public function terminationReason(): BelongsTo
    {
        return $this->belongsTo(TerminationReason::class, 'termination_reason_id');
    }

    /**
     * @return HasMany<LeaveBalance, $this>
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'contract_id');
    }

    /**
     * @return HasMany<ContractPayTier, $this>
     */
    public function payTiers(): HasMany
    {
        return $this->hasMany(ContractPayTier::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ContractAmendment, $this>
     */
    public function amendments(): HasMany
    {
        return $this->hasMany(ContractAmendment::class)->orderBy('effective_from')->orderBy('number');
    }

    /**
     * The amendments in effect, oldest first: the contract's history of terms.
     *
     * @return HasMany<ContractAmendment, $this>
     */
    public function appliedAmendments(): HasMany
    {
        return $this->hasMany(ContractAmendment::class)
            ->where('status', ContractAmendmentStatus::Applied->value)
            ->orderBy('effective_from')
            ->orderBy('number');
    }

    /**
     * The contract as it was signed: this row with the amendable terms put back
     * to what the first amendment in effect recorded as its "before".
     *
     * The row itself holds the *current* terms, which is what leave pricing,
     * exports and renewals need; the contract document (its page and its PDF)
     * is the signed paper, and an amendment is a sheet of its own beside it
     * ("يحافظ على كافة بنود العقد الأساسي"). In memory only — never saved.
     */
    public function asSigned(): self
    {
        $first = $this->relationLoaded('appliedAmendments')
            ? $this->appliedAmendments->first()
            : $this->appliedAmendments()->with('payTiers')->first();

        if ($first === null) {
            return $this;
        }

        $signed = clone $this;
        $signed->forceFill($first->terms_before ?? []);
        $signed->salary = $first->salary_before;
        $signed->setRelation('payTiers', $first->payTiersBefore()->get()->map(
            fn (ContractAmendmentPayTier $tier) => new ContractPayTier([
                'condition_en' => $tier->condition_en,
                'condition_ar' => $tier->condition_ar,
                'amount' => $tier->amount,
                'sort_order' => $tier->sort_order,
            ]),
        ));

        return $signed;
    }

    /**
     * Only a contract in force can be amended: not a draft, not terminated,
     * and today within its term. A contract yet to start is still edited
     * directly; one that has ended has nothing left to amend.
     */
    public function canBeAmended(): bool
    {
        $today = now()->startOfDay();

        return $this->is_active
            && (int) $this->type !== ContractTypeEnum::Draft->value
            && ! $this->isTerminated()
            && $this->start_date !== null && $this->start_date->lte($today)
            && $this->end_date !== null && $this->end_date->gte($today);
    }

    /**
     * Once an amendment has changed a contract's terms, they change only
     * through amendments, or the history they record stops being true.
     */
    public function hasAppliedAmendments(): bool
    {
        return $this->amendments()->where('status', ContractAmendmentStatus::Applied->value)->exists();
    }

    public function nextAmendmentNumber(): int
    {
        // Not through amendments(): its ORDER BY on an aggregate trips MySQL's ONLY_FULL_GROUP_BY.
        return (int) ContractAmendment::query()->where('contract_id', $this->getKey())->max('number') + 1;
    }

    /**
     * The dates a renewal of this contract should default to: it picks up the
     * day after this one ends and runs for the same number of months.
     *
     * The length is measured in whole months rather than days so a Jan–Jun
     * contract renews as Jul–Dec instead of ending three days short, and it is
     * clamped to [1, 12] because ContractForm::validateTerms() rejects a term
     * longer than a year and a zero-month term would end before it starts.
     *
     * @return array{0: string, 1: string} [start_date, end_date]
     */
    public function renewalWindow(): array
    {
        $sourceStart = Carbon::parse($this->start_date);
        $sourceEnd = Carbon::parse($this->end_date);

        $start = $sourceEnd->copy()->addDay();
        $months = min(12, max(1, (int) round($sourceStart->diffInMonths($sourceEnd))));

        return [
            $start->toDateString(),
            $start->copy()->addMonthsNoOverflow($months)->subDay()->toDateString(),
        ];
    }

    /**
     * Is a contract created from $sourceId onto $positionId a renewal in place?
     *
     * Such a renewal does not add headcount — the seat is already counted for
     * this employee — so the position capacity check is skipped for it, which
     * is what makes renewing a max_emp = 1 position possible at all. Renewing
     * onto a *different* position is a real new assignment and stays checked.
     */
    public static function isRenewalOfPosition(?int $sourceId, ?int $positionId): bool
    {
        return $sourceId !== null
            && $positionId !== null
            && static::query()
                ->whereKey($sourceId)
                ->where('position_id', $positionId)
                ->exists();
    }

    public function isRenewalInPlace(): bool
    {
        return static::isRenewalOfPosition($this->renewalSourceId, $this->position_id);
    }

    /**
     * A termination is recorded by either half of it: the date, or the reason.
     * Same rule ContractForm and ContractEdit use to deactivate on save.
     */
    public function isTerminated(): bool
    {
        return filled($this->terminated_at) || filled($this->termination_reason_id);
    }

    /**
     * A trial is meant to end and be evaluated, never roll over silently.
     * Keyed off config('contracts.map_by_id'), the same source as
     * ContractType::$category, without loading the type row.
     */
    public static function isTrialType(?int $contractTypeId): bool
    {
        return (config('contracts.map_by_id', ContractType::CATEGORY_BY_ID)[$contractTypeId] ?? null) === 'trial';
    }

    public function isTrial(): bool
    {
        return static::isTrialType($this->contract_type_id);
    }

    /**
     * A service agreement, not employment: keyed off the same config map.
     */
    public function isServiceAgreement(): bool
    {
        return (config('contracts.map_by_id', ContractType::CATEGORY_BY_ID)[$this->contract_type_id] ?? null) === 'service';
    }

    /**
     * May this contract be set to renew automatically at all? Neither a
     * terminated contract nor a trial ever may — ContractObserver::saving()
     * forces the flag off for both.
     */
    public function canAutoRenew(): bool
    {
        return ! $this->isTerminated() && ! $this->isTrial();
    }

    /**
     * Contract length in months, one decimal.
     */
    public function getDurationAttribute(): float
    {
        $start_date = Carbon::parse($this->start_date);
        $end_date = Carbon::parse($this->end_date);

        return round($start_date->diffInMonths($end_date), 1);
    }

    public function getWorkHourPerDayAttribute(): int
    {
        return $this->weekly_hours / 5;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * Contracts on positions in one governorate (or any of several); no filter for null.
     *
     * @param  Builder<self>  $query
     * @param  int|list<int>|null  $cityIds
     */
    #[Scope]
    protected function forCity(Builder $query, int|array|null $cityIds): void
    {
        if ($cityIds !== null) {
            $query->whereHas('position', fn (Builder $position) => $position->whereIn('city_id', (array) $cityIds));
        }
    }

    /**
     * Contracts whose employee matches the search (see Employee::search()).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (trim((string) $term) !== '') {
            $query->whereHas('employee', fn (Builder $employee) => $employee->search($term));
        }
    }
}
