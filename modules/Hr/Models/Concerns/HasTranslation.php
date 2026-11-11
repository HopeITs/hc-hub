<?php

declare(strict_types=1);

namespace Modules\Hr\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Bilingual columns stored as `{base}_en` / `{base}_ar`: `$model->name`
 * reads and writes the one for the interface language.
 *
 * @phpstan-require-extends Model
 */
trait HasTranslation
{
    /**
     * The `{base}_{locale}` column for the interface language.
     */
    public static function translatedColumn(string $base, ?string $locale = null): string
    {
        $locale ??= App::getLocale();

        return $base.'_'.($locale === 'ar' ? 'ar' : 'en');
    }

    protected function getTranslatableAttribute(string $attribute): ?string
    {
        $value = $this->getAttribute(static::translatedColumn($attribute));

        return $value === null ? null : (string) $value;
    }

    protected function setTranslatableAttribute(string $attribute, mixed $value): void
    {
        $this->attributes[static::translatedColumn($attribute)] = $value;
    }

    /**
     * An accessor/mutator pair over the interface-language column.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function translatable(string $base): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getTranslatableAttribute($base),
            set: fn (?string $value): array => [static::translatedColumn($base) => $value],
        );
    }
}
