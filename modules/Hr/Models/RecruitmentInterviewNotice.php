<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One email to the candidate about one interview: that it was scheduled,
 * moved or cancelled, or a reminder before it.
 *
 * Like RecruitmentRejectionNotice it carries no note, reason or evaluation —
 * the mail is built from the interview's public facts only. scheduled_for is
 * the slot the email describes; a worker that finds the interview has moved
 * or been cancelled since then cancels the email instead of sending it.
 *
 * @property int $id
 * @property int $interview_id
 * @property string $kind
 * @property mixed $scheduled_for
 * @property int|null $offset_minutes
 * @property string $recipient_email
 * @property string $status
 * @property int $attempts
 * @property int|null $requested_by_user_id
 * @property mixed $queued_at
 * @property mixed|null $sent_at
 * @property mixed|null $failed_at
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentInterviewNotice extends Model
{
    public const KIND_SCHEDULED = 'scheduled';

    public const KIND_RESCHEDULED = 'rescheduled';

    public const KIND_CANCELLED = 'cancelled';

    public const KIND_REMINDER = 'reminder';

    public const STATUS_QUEUED = 'queued';

    /**
     * Claimed by a worker; the mail may or may not have gone out.
     */
    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * Superseded before it went out: the interview moved or was cancelled.
     */
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentInterview, $this>
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(RecruitmentInterview::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'offset_minutes' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
