<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $position_id
 * @property int $created_from_request_id
 * @property string $status
 * @property int $needed_count
 * @property int $filled_count
 * @property mixed $open_at
 * @property mixed|null $closed_at
 * @property string|null $close_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Vacancy extends Model
{
    /**
     * The only three values `status` may hold.
     *
     * The column is a plain string with nothing behind it, so without a single
     * spelling to refer to, one writer ends up saying `'close'` and the row
     * silently drops out of every query that filters on the other.
     */
    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public const FILLED = 'filled';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<RecruitmentRequest, $this>
     */
    public function createdFromRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'created_from_request_id');
    }

    /**
     * @return HasMany<HiringCampaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(HiringCampaign::class);
    }

    /**
     * @return HasMany<RecruitmentApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class);
    }

    /**
     * @return HasMany<RecruitmentJobOffer, $this>
     */
    public function jobOffers(): HasMany
    {
        return $this->hasMany(RecruitmentJobOffer::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->whereNull('closed_at')
            ->where('open_at', '<=', now())
            ->whereColumn('filled_count', '<', 'needed_count');
    }

    protected function casts(): array
    {
        return [
            'needed_count' => 'integer',
            'filled_count' => 'integer',
            'open_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
