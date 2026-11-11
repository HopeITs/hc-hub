<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * The one row of matching-rule overrides; see RecruitmentIndicatorRules.
 *
 * @property int $id
 * @property array<mixed> $overrides_json
 * @property int|null $updated_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentIndicatorSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['overrides_json' => 'array'];
    }
}
