<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use App\Concerns\RecordsActivity;
use App\Notifications\TracksActivity;
use App\Support\Like;
use Carbon\CarbonImmutable;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Concerns\RemembersLookup;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\DonorAttribution;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\StrategicPillar;
use Modules\Tracking\Models\Strategy;
use Modules\Tracking\Observers\ProgramObserver;

/**
 * A delivery program, shared by every module (Core). HR reads it; Tracking
 * owns the strategy, pillar, manager and donor attribution, and creates a
 * program when a proposal location opens a new one (CreateLocationProgram).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description_en
 * @property string|null $description_ar
 * @property string|null $code
 * @property string $slug
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property bool $is_active
 * @property int|null $strategy_id
 * @property int|null $strategic_pillar_id
 * @property int|null $manager_id
 * @property DonorAttribution $donor_attribution
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description_en', 'description_ar', 'code', 'slug', 'start_date', 'end_date', 'is_active', 'strategy_id', 'strategic_pillar_id', 'manager_id', 'donor_attribution'])]
#[ObservedBy(ProgramObserver::class)]
class Program extends Model implements TracksActivity
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, RecordsActivity, RemembersLookup;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'donor_attribution' => 'shared',
        'is_active' => true,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'donor_attribution' => DonorAttribution::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Delete the model inside a transaction.
     *
     * Its observer deletes this program's annual plans, whose own observer
     * reaches the targets planned in them. Those deletions bracket the delete of
     * this row, so a failure partway through must not leave them committed.
     */
    public function delete(): bool
    {
        return (bool) DB::transaction(fn () => parent::delete());
    }

    /**
     * Programs ordered for a picker by name.
     *
     * @return Collection<int, static>
     */
    public static function options(): Collection
    {
        return static::query()->select(['id', 'name', 'slug', 'manager_id'])->orderByName()->get();
    }

    /**
     * Every program's name by id (remembered), for labels that hold only an id.
     *
     * @return array<int, string>
     */
    public static function namesById(): array
    {
        return static::rememberLookup('names', fn (): array => static::query()
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->map(fn (mixed $name): string => trim((string) $name))
            ->all());
    }

    /**
     * One program's name, empty for an unknown id.
     */
    public static function nameOf(?int $id): string
    {
        return $id === null ? '' : (static::namesById()[$id] ?? '');
    }

    /**
     * Every program's slug by id (remembered).
     *
     * @return array<int, string>
     */
    private static function slugsById(): array
    {
        return static::rememberLookup('slugs', fn (): array => static::query()
            ->pluck('slug', 'id')
            ->map(fn (mixed $slug): string => trim((string) $slug))
            ->all());
    }

    /**
     * The program name. Read from the row when it was selected, otherwise
     * from the remembered lookup, so a relation loaded as `program:id` still
     * shows its name without a query per row.
     *
     * @return Attribute<string, string|null>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => array_key_exists('name', $this->attributes)
                ? trim((string) $value)
                : static::nameOf($this->getKey() === null ? null : (int) $this->getKey()),
        );
    }

    /**
     * The program slug, the route key (`programs/{program}`). Read like the name.
     *
     * @return Attribute<string, string|null>
     */
    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => array_key_exists('slug', $this->attributes)
                ? trim((string) $value)
                : (self::slugsById()[(int) $this->getKey()] ?? ''),
        );
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Programs whose name contains the search (Arabic letters folded, also
     * read on the other keyboard layout).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNameLike(Builder $query, string $search, bool $acrossLayouts = true): void
    {
        $query->where(Like::matches($query->qualifyColumn('name'), $search, $acrossLayouts));
    }

    /**
     * Order programs by name, then id.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function orderByName(Builder $query, string $direction = 'asc'): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderBy($query->qualifyColumn('name'), $direction)->orderBy($query->qualifyColumn('id'), $direction);
    }

    /**
     * The strategy this program delivers against.
     *
     * @return BelongsTo<Strategy, $this>
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    /**
     * The strategic pillar this program sits under (D-35), always one of its
     * own strategy's. Null = "No pillar".
     *
     * @return BelongsTo<StrategicPillar, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(StrategicPillar::class, 'strategic_pillar_id');
    }

    /**
     * The employee accountable for this program.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * The projects delivered under this program.
     *
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * The annual operational plans written for this program.
     *
     * @return HasMany<AnnualPlan, $this>
     */
    public function annualPlans(): HasMany
    {
        return $this->hasMany(AnnualPlan::class);
    }

    /**
     * The logframe results this program is measured by: its program
     * framework only. A proposal framework's nodes carry the program too but
     * belong to the proposal (contracts §11.2, Proposal::results()).
     *
     * @return HasMany<Result, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class)->programFramework();
    }

    public function activityModule(): string
    {
        return 'programs';
    }

    public function activityLabel(): string
    {
        return $this->name;
    }

    public function activityProgramId(): ?int
    {
        return $this->getKey() === null ? null : (int) $this->getKey();
    }

    /**
     * @return array<string, string>
     */
    public function activityEvents(): array
    {
        return ['manager_id' => 'manager_changed', 'strategy_id' => 'updated', 'strategic_pillar_id' => 'updated', 'donor_attribution' => 'updated'];
    }

    /**
     * @return list<string>
     */
    public function activityEmployeeFields(): array
    {
        return ['manager_id'];
    }

    /**
     * The manager, remembered for the deleted event.
     *
     * @return array<string, mixed>
     */
    protected function activityPropertiesBeforeDelete(): array
    {
        return ['employee_ids' => array_filter([(int) $this->activityAttribute('manager_id')])];
    }
}
