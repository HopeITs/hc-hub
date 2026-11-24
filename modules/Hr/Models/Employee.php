<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use App\Concerns\RecordsActivity;
use App\Notifications\TracksActivity;
use App\Support\Like;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Concerns\HasTranslation;
use Modules\Hr\Models\Concerns\PresentsEmployee;
use Modules\Hr\Observers\HrStatisticsObserver;

/**
 * @property int $id
 * @property int|null $city_id
 * @property string $first_name_en
 * @property string $first_name_ar
 * @property string $last_name_en
 * @property string $last_name_ar
 * @property string|null $father_name_en
 * @property string|null $father_name_ar
 * @property string|null $mother_name_en
 * @property string|null $mother_name_ar
 * @property string|null $username
 * @property string|null $national_number
 * @property mixed|null $actual_dob
 * @property mixed|null $dob
 * @property string|null $pob_en
 * @property string|null $pob_ar
 * @property string|null $civil_registry_office
 * @property string|null $civil_registry_number
 * @property int|null $gender
 * @property int|null $nationality
 * @property int|null $marital_status
 * @property int|null $children
 * @property string|null $work_phone
 * @property string|null $personal_phone
 * @property string|null $personal_email
 * @property string|null $whatsapp_work
 * @property string|null $telegram_work
 * @property string|null $facebook
 * @property string|null $instagram
 * @property string|null $linkedin
 * @property string|null $address_en
 * @property string|null $address_ar
 * @property string|null $skills
 * @property string|null $interests
 * @property bool $is_active
 * @property CarbonImmutable|null $archived_at when the employee left and was archived instead of deleted
 * @property int|null $archived_by the login that archived them
 * @property CarbonImmutable|null $joined_at
 * @property bool $is_board_member
 * @property mixed|null $board_member_started_at
 * @property mixed|null $board_member_end_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $digital_id_token
 * @property bool $digital_id_enabled
 * @property mixed|null $digital_id_rotated_at
 * @property mixed|null $digital_id_revoked_at
 * @property int $digital_id_card_index
 * @property string $record_status
 * @property int|null $last_saved_by
 *
 * Tracking's relations on an employee (managedAnnualPlans, projects,
 * assignedTasks) are registered by
 * TrackingServiceProvider, so HR does not depend on Tracking.
 */
#[ObservedBy(HrStatisticsObserver::class)]
#[Fillable(['city_id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar', 'father_name_en', 'father_name_ar', 'mother_name_en', 'mother_name_ar', 'username', 'national_number', 'actual_dob', 'dob', 'pob_en', 'pob_ar', 'civil_registry_office', 'civil_registry_number', 'gender', 'nationality', 'marital_status', 'children', 'work_phone', 'personal_phone', 'personal_email', 'whatsapp_work', 'telegram_work', 'facebook', 'instagram', 'linkedin', 'address_en', 'address_ar', 'skills', 'interests', 'is_active', 'is_board_member', 'board_member_started_at', 'board_member_end_at', 'joined_at'])]
class Employee extends Model implements TracksActivity
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, HasTranslation, PresentsEmployee, RecordsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'datetime',
            'actual_dob' => 'datetime',
            'board_member_started_at' => 'datetime',
            'board_member_end_at' => 'datetime',
            'joined_at' => 'date',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
            'is_board_member' => 'boolean',
            'children' => 'integer',
            'digital_id_enabled' => 'boolean',
            'digital_id_rotated_at' => 'datetime',
            'digital_id_revoked_at' => 'datetime',
            // Deliberately not fillable: only DigitalIdManager moves it, forward.
            'digital_id_card_index' => 'integer',
        ];
    }

    /*
     * B5 (DEC-04, docs/ux/employee-lifecycle.md): an incomplete record being
     * entered by HR is a draft; everything else is complete. A draft is always
     * inactive, but inactive does not mean draft: never use is_active alone
     * for that. record_status is not fillable; only the onboarding code sets it.
     */
    public const RECORD_DRAFT = 'draft';

    public const RECORD_COMPLETE = 'complete';

    /**
     * Days after which an untouched draft is listed for HR review (B5D-Q7).
     */
    public const DRAFT_REVIEW_DAYS = 90;

    /**
     * Days after which a complete volunteer still awaiting a contract is listed for review (B5D-Q5).
     */
    public const AWAITING_REVIEW_DAYS = 60;

    /**
     * Memoized result of latestActiveContract() — not a database column.
     */
    protected ?Contract $latestActiveContractMemo = null;

    protected bool $latestActiveContractResolved = false;

    /**
     * Memoized result of isOnLeaveToday() — not a database column.
     */
    protected ?bool $onLeaveTodayMemo = null;

    public function activityModule(): string
    {
        return 'hr-employees';
    }

    public function activityProgramId(): ?int
    {
        return null;
    }

    public function activityLabel(): string
    {
        return trim($this->activityAttribute('first_name_en').' '.$this->activityAttribute('last_name_en'));
    }

    /**
     * @return array<string, string>
     */
    public function activityEvents(): array
    {
        return ['is_active' => 'status_changed'];
    }

    /**
     * HR events are logged for the activity log and the profile timeline only.
     */
    protected function activityNotifies(): bool
    {
        return false;
    }

    /**
     * Identity, contact and security columns never reach the log, which more
     * people can read than the private profile.
     *
     * @return list<string>
     */
    protected function activityExcludedFields(): array
    {
        return [
            'national_number', 'civil_registry_office', 'civil_registry_number', 'actual_dob', 'dob',
            'pob_en', 'pob_ar', 'father_name_en', 'father_name_ar', 'mother_name_en', 'mother_name_ar',
            'personal_phone', 'personal_email', 'address_en', 'address_ar',
            'digital_id_token', 'digital_id_enabled', 'digital_id_rotated_at', 'digital_id_revoked_at', 'digital_id_card_index',
            'last_saved_by',
        ];
    }

    public function nameForLocale(string $locale): string
    {
        $locale = in_array($locale, ['en', 'ar'], true) ? $locale : app()->getLocale();
        $name = trim(implode(' ', array_filter([
            $this->getAttribute("first_name_{$locale}"),
            $this->getAttribute("last_name_{$locale}"),
        ])));

        return $name !== '' ? $name : trim((string) $this->name);
    }

    /**
     * The department of the current position, if any.
     */
    public function getDepartmentAttribute(): ?Department
    {
        return $this->latestActiveContract()?->position?->department;
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastSavedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_saved_by');
    }

    public function isDraft(): bool
    {
        return $this->record_status === self::RECORD_DRAFT;
    }

    /**
     * Whether the employee left and was archived (ArchiveEmployee).
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Complete, not active, and never held a real contract: "Ready, awaiting contract".
     */
    public function isAwaitingContract(): bool
    {
        if ($this->isDraft() || $this->is_active) {
            return false;
        }

        // Lists load this once with withExists(['realContracts as has_real_contract']).
        $hasRealContract = array_key_exists('has_real_contract', $this->attributes)
            ? (bool) $this->attributes['has_real_contract']
            : $this->realContracts()->exists();

        return ! $hasRealContract;
    }

    /**
     * Contracts other than Draft-type position reservations.
     *
     * @return HasMany<Contract, $this>
     */
    public function realContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'employee_id')->where('type', '!=', ContractTypeEnum::Draft->value);
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
     * Employees who left and were archived instead of deleted.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->whereNotNull($query->qualifyColumn('archived_at'));
    }

    /**
     * Employees living in one governorate (or in any of several); no filter for null.
     *
     * @param  Builder<self>  $query
     * @param  int|list<int>|null  $cityIds
     */
    #[Scope]
    protected function forCity(Builder $query, int|array|null $cityIds): void
    {
        if ($cityIds !== null) {
            $query->whereIn($query->qualifyColumn('city_id'), (array) $cityIds);
        }
    }

    /**
     * Employees whose English or Arabic first/last name, username or national
     * number contains the search.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereLike($query->qualifyColumn('first_name_en'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('last_name_en'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('first_name_ar'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('last_name_ar'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('username'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('national_number'), "%{$term}%"));
    }

    /**
     * Employees whose English or Arabic name matches the search: every word
     * must appear in one of the four name columns.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNameLike(Builder $query, string $search, bool $acrossLayouts = true): void
    {
        $query->where(Like::everyWord($search, fn (Builder|QueryBuilder $query, string $word) => $query
            ->where(Like::word('employees.first_name_en', $word))
            ->orWhere(Like::word('employees.last_name_en', $word))
            ->orWhere(Like::word('employees.first_name_ar', $word))
            ->orWhere(Like::word('employees.last_name_ar', $word)), $acrossLayouts));
    }

    /**
     * The programs this employee manages (Tracking).
     *
     * @return HasMany<Program, $this>
     */
    public function managedPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'manager_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('employees.record_status', self::RECORD_DRAFT);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCompleteRecords(Builder $query): Builder
    {
        return $query->where('employees.record_status', self::RECORD_COMPLETE);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAwaitingContract(Builder $query): Builder
    {
        return $query->completeRecords()
            ->where('employees.is_active', false)
            ->whereDoesntHave('realContracts');
    }

    /**
     * Who may be given a contract in the composer: active staff, as before,
     * plus complete volunteers awaiting their first contract. Never a draft
     * (AC-B5-02), and not a former employee: re-hiring still starts with HR
     * re-activating them, as it always has.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeContractable(Builder $query): Builder
    {
        return $query->completeRecords()->where(fn (Builder $q) => $q
            ->where('employees.is_active', true)
            ->orWhereDoesntHave('realContracts'));
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function allContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'employee_id');
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'employee_id')
            ->activeNow();

    }

    public function latestActiveContract(): ?Contract
    {
        if ($this->latestActiveContractResolved) {
            return $this->latestActiveContractMemo;
        }

        // Reuse the eager-loaded `contracts` relation when present so callers
        // that iterate many employees don't trigger one query per row.
        $contract = $this->relationLoaded('contracts')
            ? $this->contracts->sortBy([
                ['start_date', 'desc'],
                ['id', 'desc'],
            ])->first()
            : $this->contracts()
                ->with(['position.department', 'leaveBalances'])
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

        $this->latestActiveContractResolved = true;

        return $this->latestActiveContractMemo = $contract;
    }

    /**
     * The contract shown as the employee's main position: the active one whose
     * position has the highest job level (lowest grade; no level ranks last),
     * newest first on a tie. Display only: leave balances keep following
     * latestActiveContract().
     */
    public function primaryContract(): ?Contract
    {
        $contracts = $this->relationLoaded('contracts')
            ? $this->contracts
            : $this->contracts()->with('position.jobLevel')->get();

        return $contracts
            ->sortBy([
                fn (Contract $a, Contract $b): int => ($a->position?->jobLevel->grade ?? PHP_INT_MAX) <=> ($b->position?->jobLevel->grade ?? PHP_INT_MAX),
                fn (Contract $a, Contract $b): int => $b->created_at <=> $a->created_at,
            ])
            ->first();
    }

    /**
     * Whether an accepted leave covers today: a day leave for the whole of
     * its dates, an hour leave only between its start and end times.
     */
    public function isOnLeaveToday(): bool
    {
        return $this->onLeaveTodayMemo ??= LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Accepted->value)
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('type', 'day')
                    ->whereDate('start_at', '<=', today())
                    ->whereDate('end_at', '>=', today()))
                ->orWhere(fn ($query) => $query
                    ->where('type', 'hour')
                    ->where('start_at', '<=', now())
                    ->where('end_at', '>=', now())))
            ->whereHas('leaveBalance.contract', fn ($query) => $query->where('employee_id', $this->id))
            ->exists();
    }

    /**
     * @return HasMany<DefaultDeliverable, $this>
     */
    public function defaultDeliverItems(): HasMany
    {
        return $this->hasMany(DefaultDeliverable::class, 'employee_id');
    }

    /**
     * @return BelongsToMany<Position, $this>
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'contracts', 'employee_id', 'position_id')
            ->withPivot([
                'id', 'contract_type_id', 'start_date', 'end_date', 'terminated_at',
                'salary', 'currency', 'weekly_hours', 'work_days_'.(app()->getLocale() === 'ar' ? 'ar' : 'en'), 'work_hours_from', 'work_hours_to', 'is_active',
            ])
            ->wherePivot('is_active', true)
            ->where(function (Builder $query): void {
                $today = now()->toDateString();
                $query->whereDate('contracts.start_date', '<=', $today)
                    ->whereDate('contracts.end_date', '>=', $today);
            });
    }

    /**
     * Employees holding a current contract on a position of the program.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function involvedInProgram(Builder $query, int $programId): void
    {
        $query->whereHas('contracts.position', fn (Builder $position) => $position->where('program_id', $programId));
    }

    /**
     * @return HasMany<Education, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return HasMany<Experience, $this>
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    /**
     * @return HasMany<Badge, $this>
     */
    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    /**
     * @return HasMany<Evaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * @return HasMany<Relative, $this>
     */
    public function relatives(): HasMany
    {
        return $this->hasMany(Relative::class);
    }

    /**
     * @return HasMany<Checklist, $this>
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    /**
     * @return HasMany<EmployeeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EmployeeItem::class);
    }

    /**
     * @return BelongsToMany<JobLevel, $this>
     */
    public function jobLevels(): BelongsToMany
    {
        return $this->belongsToMany(JobLevel::class, 'contracts', 'employee_id', 'job_level_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'work_days', 'work_hours']);
    }

    /**
     * @return BelongsToMany<ContractType, $this>
     */
    public function contractTypes(): BelongsToMany
    {
        return $this->belongsToMany(ContractType::class, 'contracts', 'employee_id', 'contract_type_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'work_days', 'work_hours']);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function account(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    /**
     * D4-I: the onboarding journey started by the first real contract, if any.
     *
     * @return HasOne<OnboardingJourney, $this>
     */
    public function onboardingJourney(): HasOne
    {
        return $this->hasOne(OnboardingJourney::class);
    }

    /**
     * DEC-09 increment 2: one exit journey per departure (employee + last contract).
     *
     * @return HasMany<OffboardingJourney, $this>
     */
    public function offboardingJourneys(): HasMany
    {
        return $this->hasMany(OffboardingJourney::class);
    }

    /**
     * The most recent departure, which the edit page's exit panel shows.
     *
     * @return HasOne<OffboardingJourney, $this>
     */
    public function latestOffboardingJourney(): HasOne
    {
        return $this->hasOne(OffboardingJourney::class)->latestOfMany();
    }

    /**
     * The current contract's leave balances that still have days left.
     *
     * @return Collection<int, LeaveBalance>|null
     */
    public function allAvailableLeaves(): ?Collection
    {
        return $this->latestActiveContract()?->leaveBalances()->whereColumn('deserved', '>', 'used')->with('leaveType')->get();
    }

    /**
     * Start of the person's service. The stored `joined_at` is the
     * authoritative organisational joining date; the earliest contract's
     * start_date is only a fallback for employees whose joining date has not
     * been recorded yet (imported contracts often start well after the person
     * actually joined).
     */
    public function serviceStartDate(): ?CarbonImmutable
    {
        if ($this->joined_at !== null) {
            return $this->joined_at->startOfDay();
        }

        $earliestContractStart = $this->allContracts()->min('start_date');

        return $earliestContractStart
            ? CarbonImmutable::parse((string) $earliestContractStart)->startOfDay()
            : null;
    }

    /**
     * End of the person's service. Someone who has left stops accruing on the
     * day their last contract ended (or the day it was terminated early);
     * everyone still active keeps accruing up to today.
     */
    public function serviceEndDate(): CarbonImmutable
    {
        $today = CarbonImmutable::today();

        if ($this->is_active) {
            return $today;
        }

        $lastContractEnd = $this->allContracts()
            ->select(['end_date', 'terminated_at'])
            ->get()
            ->map(fn (Contract $contract) => $contract->terminated_at ?? $contract->end_date)
            ->filter()
            ->max();

        if (! $lastContractEnd) {
            return $today;
        }

        // A contract ending in the future does not accrue service in advance.
        return CarbonImmutable::parse($lastContractEnd)->startOfDay()->min($today);
    }

    /**
     * Completed years of service, by default over the person's whole service
     * span. Pass $asOf to get the seniority they had on a given day.
     *
     * Drives the annual leave entitlement — @see \Modules\Tracking\Services\LeaveBalanceService
     */
    public function yearsOfServiceOn(CarbonInterface|string|null $asOf = null): int
    {
        $start = $this->serviceStartDate();

        if (! $start) {
            return 0;
        }

        $end = $this->serviceEndDate();

        if ($asOf !== null) {
            $end = CarbonImmutable::parse($asOf)->startOfDay()->min($end);
        }

        return $end->lte($start) ? 0 : (int) floor($start->diffInYears($end));
    }

    /**
     * Service length as "3 years 5 months", counted continuously from the
     * joining date — gaps between contracts no longer interrupt it.
     */
    public function getYearsOfServiceFormattedAttribute(): string
    {
        $start = $this->serviceStartDate();
        $end = $this->serviceEndDate();

        if (! $start || $end->lte($start)) {
            return trans_choice('hr_employees.count_years', 0);
        }

        $years = $this->yearsOfServiceOn();
        $months = (int) floor($start->copy()->addYears($years)->diffInMonths($end));

        $parts = array_filter([
            $years > 0 ? trans_choice('hr_employees.count_years', $years, ['count' => $years]) : null,
            $months > 0 ? trans_choice('hr_employees.count_months', $months, ['count' => $months]) : null,
        ]);

        return $parts !== [] ? implode(' ', $parts) : trans_choice('hr_employees.count_years', 0);
    }

    public function getYearsOfServiceAttribute(): int
    {
        return $this->yearsOfServiceOn();
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            // No longer read by the card (its link is DigitalIdManager's), kept
            // so the previous release still works if it has to be redeployed.
            // A draft gets none (B5-D §5.3); it is issued at activation.
            if (! $employee->isDraft()) {
                $employee->digital_id_token ??= Str::random(64);
            }
        });
    }

    /**
     * "First Father Last" in the interface language.
     *
     * @return Attribute<string, never>
     */
    protected function longFullname(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->father_name} {$this->last_name}"));
    }

    /**
     * "First Last" in the interface language.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            $name = trim("{$this->first_name} {$this->last_name}");

            return $name !== '' ? $name : $this->name_en;
        });
    }

    /**
     * The organization email — the linked account's login. Anything shown to
     * colleagues uses this and work_phone, never personal_email/personal_phone,
     * which stay on the HR-only private profile section.
     *
     * @return Attribute<string|null, never>
     */
    protected function workEmail(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->account?->getAttributeValue('email'));
    }

    /**
     * A mailto: link that opens a new message to this employee's work email,
     * optionally copying another address (e.g. their direct manager). Null
     * when there is no work email. A CC equal to the recipient is dropped.
     */
    public function mailtoUrl(?string $cc = null): ?string
    {
        $this->loadMissing('account');
        $to = $this->work_email;

        if (! filled($to)) {
            return null;
        }

        $cc = filled($cc) && strcasecmp($cc, $to) !== 0 ? $cc : null;

        return 'mailto:'.rawurlencode($to).($cc !== null ? '?cc='.rawurlencode($cc) : '');
    }

    /**
     * The work email of each employee's current direct manager, keyed by
     * employee id (a missing key means no manager, a vacant post, or a
     * manager without a work email). Three queries whatever the number of
     * employees, so lists can use it without an N+1.
     *
     * @param  iterable<Employee|int>  $employees
     * @return array<int, string>
     */
    public static function directManagerEmails(iterable $employees): array
    {
        $ids = collect($employees)
            ->map(fn (Employee|int $employee): int => $employee instanceof self ? (int) $employee->getKey() : $employee)
            ->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        // Oldest first, so the latest running contract wins for someone with several.
        $positionOf = Contract::query()->activeNow()
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->whereIn('employee_id', $ids)
            ->orderBy('start_date')
            ->pluck('position_id', 'employee_id');

        $managerPositionOf = Position::query()
            ->whereIn('id', $positionOf->unique()->values())
            ->whereNotNull('direct_manager_id')
            ->pluck('direct_manager_id', 'id');

        $emailAt = Contract::query()->activeNow()
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->whereIn('position_id', $managerPositionOf->unique()->values())
            ->whereHas('employee', fn (Builder $employee) => $employee->where('is_active', true))
            ->with('employee.account:id,employee_id,email')
            ->orderBy('start_date')
            ->get(['id', 'employee_id', 'position_id', 'start_date'])
            ->mapWithKeys(fn (Contract $contract): array => [(int) $contract->position_id => $contract->employee?->work_email])
            ->filter();

        $emails = [];

        foreach ($positionOf as $employeeId => $positionId) {
            $managerPositionId = $managerPositionOf[$positionId] ?? null;
            $email = $managerPositionId === null ? null : ($emailAt[$managerPositionId] ?? null);

            if (filled($email)) {
                $emails[(int) $employeeId] = (string) $email;
            }
        }

        return $emails;
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function firstName(): Attribute
    {
        return $this->translatable('first_name');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function lastName(): Attribute
    {
        return $this->translatable('last_name');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function fatherName(): Attribute
    {
        return $this->translatable('father_name');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function motherName(): Attribute
    {
        return $this->translatable('mother_name');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function address(): Attribute
    {
        return $this->translatable('address');
    }
}
