<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $campaign_id
 * @property string $full_name
 * @property string $email_normalized
 * @property string $status
 * @property mixed|null $sent_at
 * @property mixed|null $failed_at
 * @property string|null $failure_reason
 * @property mixed|null $used_at
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentCampaignInvite extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_USED = 'used';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Why the last email did not arrive; the screens translate these.
     */
    public const FAILURE_MAILBOX_MISSING = 'mailbox_missing';

    public const FAILURE_REJECTED = 'rejected';

    public const FAILURE_NOT_SENT = 'not_sent';

    protected $guarded = ['id'];

    /**
     * Did the last attempt to email this invitation fail?
     *
     * Beside the status rather than instead of it: the invitation is still
     * valid, and an invitation that has been used or cancelled has nothing
     * left to deliver.
     */
    public function hasDeliveryFailed(): bool
    {
        return $this->failure_reason !== null
            && ! in_array($this->status, [self::STATUS_USED, self::STATUS_CANCELLED], true);
    }

    /**
     * @return BelongsTo<HiringCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(HiringCampaign::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasOne<RecruitmentApplication, $this>
     */
    public function application(): HasOne
    {
        return $this->hasOne(RecruitmentApplication::class, 'campaign_invite_id');
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
