<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Concerns\RemembersLookup;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description_en
 * @property string|null $description_ar
 * @property string|null $email
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'code', 'description_en', 'description_ar', 'email'])]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, HasTranslation, RemembersLookup;

    /**
     * The name of one department, empty for an unknown id (remembered).
     */
    public static function nameOf(int $id): string
    {
        return static::rememberLookup('names', fn (): array => static::query()
            ->toBase()
            ->pluck('name', 'id')
            ->map(fn (mixed $name): string => trim((string) $name))
            ->all())[$id] ?? '';
    }

    /**
     * Departments ordered for a picker, id and name only.
     *
     * @return Collection<int, static>
     */
    public static function options(): Collection
    {
        return static::query()->select(['id', 'name'])->orderBy('name')->orderBy('id')->get();
    }

    /**
     * The ids of every employee currently holding a position in this
     * department through an active contract: one join, no lookup.
     *
     * @return list<int>
     */
    public static function employeeIdsInDepartment(int $departmentId): array
    {
        return array_values(Contract::query()
            ->join('positions', 'positions.id', '=', 'contracts.position_id')
            ->where('contracts.is_active', true)
            ->where('positions.department_id', $departmentId)
            ->distinct()
            ->pluck('contracts.employee_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'department_id');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function description(): Attribute
    {
        return $this->translatable('description');
    }

    /**
     * Departments whose name or code contains the search.
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
            ->whereLike($query->qualifyColumn('name'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('code'), "%{$term}%"));
    }
}
