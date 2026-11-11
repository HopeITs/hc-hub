<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $campaign_id
 * @property string $status
 * @property int|null $current_version_id
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class JobPost extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<HiringCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(HiringCampaign::class, 'campaign_id');
    }

    /**
     * @return BelongsTo<JobPostVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(JobPostVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<JobPostVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(JobPostVersion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
