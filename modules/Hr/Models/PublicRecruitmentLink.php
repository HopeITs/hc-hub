<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $job_post_version_id
 * @property int $form_version_id
 * @property string $token_hash
 * @property string|null $url_encrypted
 * @property string $status
 * @property mixed $expires_at
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['token_hash', 'url_encrypted'])]
class PublicRecruitmentLink extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

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
    public function jobPostVersion(): BelongsTo
    {
        return $this->belongsTo(JobPostVersion::class);
    }

    /**
     * @return BelongsTo<ApplicationFormVersion, $this>
     */
    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(ApplicationFormVersion::class, 'form_version_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<RecruitmentApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'public_link_id');
    }

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
