<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use App\Enums\Locale;
use Carbon\CarbonImmutable;
use Database\Factories\CenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Core\Models\Concerns\RemembersLookup;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;

/**
 * One of the organisation's centers, in one governorate. Shared by every
 * module; ids are HR's (hub:import-hr). A governorate with centers cannot be
 * deleted.
 *
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property-read string $name
 * @property string|null $weekends
 * @property string|null $open_at
 * @property string|null $close_at
 * @property int|null $city_id
 * @property string|null $address_en
 * @property string|null $address_ar
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar', 'weekends', 'open_at', 'close_at', 'city_id', 'address_en', 'address_ar', 'is_active'])]
class Center extends Model
{
    /** @use HasFactory<CenterFactory> */
    use HasFactory, RemembersLookup;

    /**
     * Mirror the column defaults for strict mode.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'weekends' => null,
        'open_at' => '09:30:00',
        'close_at' => '17:00:00',
        'address_en' => null,
        'address_ar' => null,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'city_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Every center's name in the interface language, by id (remembered).
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

    public static function nameOf(?int $id): string
    {
        return $id === null ? '' : (static::names()[$id] ?? '');
    }

    /**
     * Active centers for a picker, optionally in one governorate
     * (remembered): id => name.
     *
     * @return array<int, string>
     */
    public static function options(?int $cityId = null): array
    {
        $column = static::nameColumn();

        return static::rememberLookup("options:{$column}:".($cityId ?? 'all'), fn (): array => static::query()
            ->active()
            ->when($cityId !== null, fn (Builder $query) => $query->where('city_id', $cityId))
            ->orderBy($column)
            ->pluck($column, 'id')
            ->map(fn (mixed $name): string => trim((string) $name))
            ->all());
    }

    public static function nameColumn(): string
    {
        return Locale::current() === Locale::Arabic ? 'name_ar' : 'name_en';
    }

    /**
     * The center's name in the interface language, falling back to English.
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
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forCity(Builder $query, ?int $cityId): void
    {
        if ($cityId !== null) {
            $query->where($query->qualifyColumn('city_id'), $cityId);
        }
    }

    /**
     * Centers whose English or Arabic name contains the search.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->where(fn (Builder $query) => $query
                ->whereLike('name_en', "%{$term}%")
                ->orWhereLike('name_ar', "%{$term}%"));
        }
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    /**
     * The contracts on positions located at this center.
     *
     * @return HasManyThrough<Contract, Position, $this>
     */
    public function contracts(): HasManyThrough
    {
        return $this->hasManyThrough(Contract::class, Position::class);
    }
}
