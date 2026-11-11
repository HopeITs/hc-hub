<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar'])]
class CourseCategory extends Model
{
    use HasTranslation;

    public function getNameAttribute(): ?string
    {
        return $this->getTranslatableAttribute('name');
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}
