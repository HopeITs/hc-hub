<?php

namespace Modules\Hr\Services\Assistant;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Hr\Models\Position;

/**
 * Typed reads of a record's relations for the assistant's tools.
 *
 * Several HR models declare their relations without generic types, so a
 * relation read straight off the model is an untyped Model to static
 * analysis. These read the same relation (loaded or lazily, exactly as the
 * property would) and return it as its own class, or null when it is
 * missing or of another class.
 */
final class Related
{
    /**
     * @template TRelated of Model
     *
     * @param  class-string<TRelated>  $class
     * @return TRelated|null
     */
    public static function one(?Model $model, string $relation, string $class): ?Model
    {
        $related = $model?->getRelationValue($relation);

        return $related instanceof $class ? $related : null;
    }

    /**
     * @template TRelated of Model
     *
     * @param  class-string<TRelated>  $class
     * @return Collection<int, TRelated>
     */
    public static function many(?Model $model, string $relation, string $class): Collection
    {
        $loaded = $model?->getRelationValue($relation);
        $items = [];

        if ($loaded instanceof Collection) {
            foreach ($loaded as $related) {
                if ($related instanceof $class) {
                    $items[] = $related;
                }
            }
        }

        return collect($items);
    }

    /**
     * Eager-loads a belongs-to relation on many records in one query, through
     * the related model's own query: what with() does, for a relation the
     * model declares without a return type. $nested are eager loads on the
     * related records.
     *
     * @param  iterable<Model>  $models
     * @param  class-string<Model>  $class
     * @param  list<string>  $nested
     */
    public static function loadOne(iterable $models, string $relation, string $foreignKey, string $class, array $nested = []): void
    {
        $models = collect($models);
        $ids = $models->map(fn (Model $model) => $model->getAttribute($foreignKey))->filter()->unique()->values()->all();
        $related = [];

        if ($ids !== []) {
            foreach ($class::query()->with($nested)->findMany($ids) as $record) {
                $related[$record->getKey()] = $record;
            }
        }

        foreach ($models as $model) {
            $model->setRelation($relation, $related[$model->getAttribute($foreignKey)] ?? null);
        }
    }

    /**
     * Eager-loads a has-many relation on many records in one query: what
     * with() does, for a relation the model declares without a return type.
     * $query is the relation's own query (its constraints included).
     *
     * @param  iterable<Model>  $models
     * @param  Builder<covariant Model>  $query
     */
    public static function loadMany(iterable $models, string $relation, string $foreignKey, Builder $query): void
    {
        $models = collect($models);
        $ids = $models->map(fn (Model $model) => $model->getKey())->unique()->values()->all();
        $related = $ids === [] ? collect() : $query->whereIn($query->qualifyColumn($foreignKey), $ids)->get()->groupBy($foreignKey);

        foreach ($models as $model) {
            $model->setRelation($relation, new EloquentCollection($related->get($model->getKey(), [])));
        }
    }

    /**
     * A text attribute of a record (a translated name or title resolves to
     * the current locale, as on the pages), or null.
     */
    public static function text(?Model $model, string $attribute = 'name'): ?string
    {
        $value = $model?->getAttribute($attribute);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Where a position sits, as the lists name it: title, department,
     * center and city (the center's city, else the position's own).
     *
     * @return array{position: ?string, department: ?string, center: ?string, city: ?string}
     */
    public static function place(?Position $position): array
    {
        $center = self::one($position, 'center', Center::class);
        $city = self::one($center, 'city', City::class) ?? self::one($position, 'city', City::class);

        return [
            'position' => self::text($position, 'title'),
            'department' => self::text(self::one($position, 'department', Department::class)),
            'center' => self::text($center),
            'city' => self::text($city),
        ];
    }
}
