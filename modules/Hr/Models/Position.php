<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property int $program_id
 * @property int|null $center_id
 * @property int $city_id
 * @property int $job_level_id
 * @property int $department_id
 * @property int|null $direct_manager_id
 * @property int|null $tech_manager_id
 * @property string $title_en
 * @property string $title_ar
 * @property string|null $qualifications
 * @property string|null $responsibilities
 * @property int $max_emp
 * @property int $exist_emp
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory, HasTranslation;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_emp' => 'integer',
            'exist_emp' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected $guarded = ['id'];

    // --------------------------------------------
    // Relationships
    // --------------------------------------------

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'position_id');
    }

    /**
     * @return HasMany<Vacancy, $this>
     */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * @return HasMany<RecruitmentRequest, $this>
     */
    public function recruitmentRequests(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class, 'target_position_id');
    }

    /**
     * Active contracts on this position. Employees may hold multiple positions
     * simultaneously; each position lists every active contract here. For the
     * employee's single "primary" contract elsewhere, use Employee::latestActiveContract().
     */
    /**
     * @return HasMany<Contract, $this>
     */
    public function activeContracts(): HasMany
    {
        return $this->contracts()
            ->activeNow()
            ->with('employee');
    }

    /**
     * @return BelongsTo<Center, $this>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * @return BelongsTo<JobLevel, $this>
     */
    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class, 'job_level_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function directManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'direct_manager_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function techManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'tech_manager_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function directTeam(): HasMany
    {
        return $this->hasMany(self::class, 'direct_manager_id')->orderByGrade();
    }

    /**
     * @return HasMany<self, $this>
     */
    public function techTeam(): HasMany
    {
        return $this->hasMany(self::class, 'tech_manager_id')->orderByGrade();
    }

    /**
     * Recursive eager-load of the full direct-reporting subtree. Laravel resolves
     * a self-referencing `with()` one depth-level per query (NOT one per node), so
     * loading an entire branch costs O(depth) queries instead of O(nodes) — this is
     * the N+1-safe way to hydrate the whole tree. The card relations are bundled in
     * so every node arrives display-ready (employee, avatar, program, tech manager).
     *
     * @return HasMany<Position, $this>
     */
    public function directTeamRecursive(): HasMany
    {
        return $this->directTeam()->with(['directTeamRecursive', ...self::cardRelations('direct')]);
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function techTeamRecursive(): HasMany
    {
        return $this->techTeam()->with(['techTeamRecursive', ...self::cardRelations('tech')]);
    }

    /**
     * Eager-load set every org-chart card needs: the holder + avatar, the lookups
     * shown on the card, and the *cross* reporting line (the dotted-line manager)
     * so the matrix badge can render without an extra query per node.
     *
     * @return list<string>
     */
    public static function cardRelations(string $line = 'direct'): array
    {
        $crossManager = $line === 'direct' ? 'techManager' : 'directManager';

        return [
            'activeContracts.employee',
            'city',
            'center.city',
            'center',
            'program',
            'department',
            'jobLevel',
            "{$crossManager}.activeContracts.employee",
            "{$crossManager}.jobLevel",
        ];
    }

    // --------------------------------------------
    // Query scopes
    // --------------------------------------------

    /**
     * Positions that manage at least one other position on the given reporting
     * line (direct or tech). Drives the "filter by manager" dropdown.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeManagersOnly(Builder $query, string $line = 'direct'): Builder
    {
        $relation = $line === 'tech' ? 'techTeam' : 'directTeam';

        return $query->whereHas($relation);
    }

    /**
     * Order positions by their job level's grade (grade 1 first), regardless of
     * the job_level_id sequence. Used so each org-chart row reads top grade first.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrderByGrade(Builder $query, string $direction = 'asc'): Builder
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        return $query->orderBy(
            JobLevel::query()
                ->select('grade')
                ->whereColumn('job_levels.id', $query->qualifyColumn('job_level_id')),
            $direction,
        );
    }

    /**
     * Only positions that should appear in the org chart / directory: the
     * position itself is active and it does not belong to a deactivated program.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true)
            ->where(function ($q) {
                $q->whereNull($q->qualifyColumn('program_id'))
                    ->orWhereHas('program', fn ($p) => $p->where('is_active', true));
            });
    }

    /**
     * Positions reachable from the given city: either assigned directly to the
     * city, or assigned to a center that belongs to it. Single source of truth
     * for city scoping; keep contract create/edit filters aligned with it.
     *
     * @param  int|array<int, int>  $cityIds  one city, or every city a role covers
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInCity(Builder $query, int|array $cityIds): Builder
    {
        $cityIds = (array) $cityIds;

        // Qualify the columns: callers such as PositionTable join tables
        // (centers, departments, ...) that also carry a city_id, which MySQL
        // rejects as ambiguous.
        return $query->where(function ($q) use ($cityIds) {
            $q->whereIn($q->qualifyColumn('city_id'), $cityIds)
                ->orWhereHas('center', fn ($c) => $c->whereIn($c->qualifyColumn('city_id'), $cityIds));
        });
    }

    /**
     * The same rule for several governorates at once. Delegates to inCity()
     * rather than restating it: a second copy of "city_id or the centre's
     * city_id" is how the two drift apart.
     *
     * @param  array<int, int>  $cityIds
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInCities(Builder $query, array $cityIds): Builder
    {
        return $this->scopeInCity($query, $cityIds);
    }

    // --------------------------------------------
    // Helpers
    // --------------------------------------------

    public function employee(): ?Employee
    {
        // Reuse the eager-loaded relation when present to avoid one query per
        // position when this is called inside table/export loops.
        if ($this->relationLoaded('activeContracts')) {
            /** @var Contract|null $contract */
            $contract = $this->activeContracts->first();

            return $contract?->employee;
        }

        return $this->activeContracts()->first()?->employee;
    }

    public function canAssign(): bool
    {
        return $this->exist_emp < $this->max_emp;
    }

    /**
     * Does this position lead a team on the given reporting line? Relies on the
     * team relation already being loaded (org chart) and falls back to a count.
     */
    public function isManagerOnLine(string $line = 'direct'): bool
    {
        $relation = $line === 'tech' ? 'techTeam' : 'directTeam';

        return $this->relationLoaded($relation)
            ? $this->getRelation($relation)->isNotEmpty()
            : $this->{$relation}()->exists();
    }

    /**
     * Number of people seated in the given positions: the distinct employees on
     * their active contracts. Drives the "direct reports" counts, which must count
     * people rather than positions — one position can seat several employees, so
     * a position count under-reported every shared role. Relies on activeContracts
     * being eager-loaded (cardRelations()).
     *
     * @param  iterable<int, self>  $positions
     */
    public static function headcount(iterable $positions): int
    {
        return collect($positions)
            ->filter(fn (self $position) => $position->is_active)
            ->flatMap(fn (self $position) => $position->activeContracts)
            ->pluck('employee_id')
            ->unique()
            ->count();
    }

    public function inc(): void
    {
        $this->increment('exist_emp');
    }

    public function dec(): void
    {
        if ($this->exist_emp > 0) {
            $this->decrement('exist_emp');
        }
    }

    // --------------------------------------------
    // Activity Logging
    // --------------------------------------------

    // --------------------------------------------
    // Accessors & Mutators
    // --------------------------------------------

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function title(): Attribute
    {
        return $this->translatable('title');
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
     * Positions based in one governorate (or any of several); no filter for null.
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
     * Positions under one program; no filter for null.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forProgram(Builder $query, ?int $programId): void
    {
        if ($programId !== null) {
            $query->where($query->qualifyColumn('program_id'), $programId);
        }
    }

    /**
     * Positions whose English or Arabic title contains the search.
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
            ->whereLike($query->qualifyColumn('title_en'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('title_ar'), "%{$term}%"));
    }
}
