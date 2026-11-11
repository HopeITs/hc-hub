<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * The rejection email for one application: whether it went, and when.
 *
 * Deliberately carries no reason and no note — the mail is built from this
 * row and the application's public facts only.
 *
 * @property int $id
 * @property int $application_id
 * @property int|null $decision_id
 * @property string $recipient_email
 * @property string $status
 * @property int $attempts
 * @property int $requested_by_user_id
 * @property mixed $queued_at
 * @property mixed|null $sent_at
 * @property mixed|null $failed_at
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentRejectionNotice extends Model
{
    public const STATUS_QUEUED = 'queued';

    /**
     * Claimed by a worker; the mail may or may not have gone out.
     */
    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * The application was reopened before the mail went out.
     */
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    /**
     * @return BelongsTo<RecruitmentApplicationDecision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicationDecision::class, 'decision_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Queued, sending or sent: another request must not send a second one.
     */
    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_SENDING, self::STATUS_SENT], true);
    }

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
