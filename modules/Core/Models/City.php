<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use App\Enums\Locale;
use Carbon\CarbonImmutable;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Core\Models\Concerns\RemembersLookup;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;

/**
 * A governorate, shared by every module. Ids are HR's (hub:import-hr).
 *
 * Id 1, slug SYR, is the pseudo-governorate "Syria": the organisation-wide
 * scope above every city, not a city beside Aleppo or Homs. `slug` is the
 * 3-letter code regional HR roles are named after (HR-ALP), `code` the
 * zero-padded number printed on digital IDs.
 *
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property-read string $name
 * @property string $slug
 * @property string $code
 * @property string|null $hr_hotline
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar', 'slug', 'code', 'hr_hotline', 'is_active'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory, RemembersLookup;

    /**
     * Slug of the nationwide pseudo-governorate.
     */
    public const string NATIONAL_SLUG = 'SYR';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'hr_hotline' => null,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Every city's name in the interface language, by id (remembered).
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        $column = static::nameColumn();

        return static::rememberLookup("names:{$column}", fn (): array => static::query()
            ->orderBy($column)
            ->pluck($column, 'id')
            ->map(fn (mixed $name): string => trim((string) $name))
            ->all());
    }

    /**
     * One city's name, or an empty string for an unknown id.
     */
    public static function nameOf(?int $id): string
    {
        return $id === null ? '' : (static::names()[$id] ?? '');
    }

    /**
     * Active cities for a picker, by name in the interface language
     * (remembered): id => name.
     *
     * @return array<int, string>
     */
    public static function options(bool $withNational = true): array
    {
        $column = static::nameColumn();

        return static::rememberLookup("options:{$column}:".(int) $withNational, fn (): array => static::query()
            ->active()
            ->when(! $withNational, fn (Builder $query) => $query->where('slug', '!=', self::NATIONAL_SLUG))
            ->orderBy($column)
            ->pluck($column, 'id')
            ->map(fn (mixed $name): string => trim((string) $name))
            ->all());
    }

    /**
     * Whether $cityId is the nationwide scope.
     */
    public static function isNational(?int $cityId): bool
    {
        return $cityId !== null && static::idsBySlug()[self::NATIONAL_SLUG] === $cityId;
    }

    /**
     * Every city id by its 3-letter slug (remembered).
     *
     * @return array<string, int>
     */
    public static function idsBySlug(): array
    {
        return static::rememberLookup('slugs', fn (): array => static::query()
            ->pluck('id', 'slug')
            ->mapWithKeys(fn (mixed $id, mixed $slug): array => [strtoupper(trim((string) $slug)) => (int) $id])
            ->all() + [self::NATIONAL_SLUG => 0]);
    }

    /**
     * The city slugs a regional HR role (HR-ALP) is responsible for:
     * config('access-control.hr_role_cities') when listed, else the slug in
     * the role's name.
     *
     * @return list<string>
     */
    public static function slugsForHrRole(string $roleName): array
    {
        $roleName = strtoupper(trim($roleName));
        $configured = config('access-control.hr_role_cities.'.$roleName);

        return is_array($configured)
            ? array_values(array_map(fn (mixed $slug): string => (string) $slug, $configured))
            : [Str::after($roleName, 'HR-')];
    }

    /**
     * The name column in the interface language.
     */
    public static function nameColumn(): string
    {
        return Locale::current() === Locale::Arabic ? 'name_ar' : 'name_en';
    }

    /**
     * The city's name in the interface language, falling back to English.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => Locale::current() === Locale::Arabic && trim($this->name_ar) !== ''
            ? trim($this->name_ar)
            : trim($this->name_en));
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
     * Cities whose English or Arabic name, slug or code contains the search.
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
            ->whereLike('name_en', "%{$term}%")
            ->orWhereLike('name_ar', "%{$term}%")
            ->orWhereLike('slug', "%{$term}%")
            ->orWhereLike('code', "%{$term}%"));
    }

    /**
     * @return HasMany<Center, $this>
     */
    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * The employees living in this governorate.
     *
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
