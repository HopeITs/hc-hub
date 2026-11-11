<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * Indicators as they were computed at one moment, under one criteria version.
 *
 * A figure without its criteria version is meaningless once the criteria
 * change, so the two are only ever written together.
 *
 * @property int $id
 * @property int $application_id
 * @property string $criteria_version
 * @property int $met_count
 * @property int $assessed_count
 * @property array<mixed> $indicators_json
 * @property mixed $computed_at
 * @property int|null $computed_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentApplicationIndicatorSnapshot extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    /**
     * @return BelongsTo<RecruitmentIndicatorCriteriaVersion, $this>
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(RecruitmentIndicatorCriteriaVersion::class, 'criteria_version', 'version');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function computedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'computed_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'met_count' => 'integer',
            'assessed_count' => 'integer',
            'indicators_json' => 'array',
            'computed_at' => 'datetime',
        ];
    }
}
