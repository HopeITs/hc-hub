<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * The definition behind one criteria version, kept so old snapshots stay explainable.
 *
 * @property int $id
 * @property string $version
 * @property array<mixed> $definition_json
 * @property CarbonImmutable $created_at
 */
class RecruitmentIndicatorCriteriaVersion extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['definition_json' => 'array'];
    }
}
