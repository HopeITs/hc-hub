<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property string $title_ar
 * @property string|null $title_en
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title_en', 'title_ar'])]
class TerminationReason extends Model
{
    use HasTranslation;

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function title(): Attribute
    {
        return $this->translatable('title');
    }
}
