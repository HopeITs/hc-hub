<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslation
{
    protected function getTranslatableAttribute(string $attribute)
    {
        $locale = App::getLocale();
        $localizedAttribute = "{$attribute}_{$locale}";

        return $this->{$localizedAttribute} ?? null;
    }

    protected function setTranslatableAttribute(string $attribute, $value): void
    {
        $locale = App::getLocale();
        $localizedAttribute = "{$attribute}_{$locale}";

        $this->attributes[$localizedAttribute] = $value;
    }
}
