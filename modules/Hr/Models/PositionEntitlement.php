<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A default of the Permissions Library: for one position, or for every
 * position of a job level.
 *
 * @property int $id
 * @property int $entitlement_id
 * @property int|null $position_id
 * @property int|null $job_level_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['entitlement_id', 'position_id', 'job_level_id'])]
class PositionEntitlement extends Model
{
    /**
     * @return BelongsTo<Entitlement, $this>
     */
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(Entitlement::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<JobLevel, $this>
     */
    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class);
    }
}
