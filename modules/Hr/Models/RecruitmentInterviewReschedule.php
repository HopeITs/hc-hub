<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\User;

/**
 * One change of an interview's slot or place: old and new values, who, when,
 * why, and whether the candidate had already been reminded of the old slot.
 * History is append-only — a row is never edited or removed.
 *
 * @property int $id
 * @property int $interview_id
 * @property mixed $from_scheduled_at
 * @property mixed|null $from_ends_at
 * @property mixed $to_scheduled_at
 * @property mixed|null $to_ends_at
 * @property string $from_location_type
 * @property string $to_location_type
 * @property bool $location_changed
 * @property bool $reminder_already_sent
 * @property string $reason
 * @property int $rescheduled_by_user_id
 * @property mixed $rescheduled_at
 */
class RecruitmentInterviewReschedule extends Model
{
    public $timestamps = false;

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
    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by_user_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Reschedule history is append-only.'));
        static::deleting(fn () => throw new LogicException('Reschedule history is append-only.'));
    }

    protected function casts(): array
    {
        return [
            'from_scheduled_at' => 'datetime',
            'from_ends_at' => 'datetime',
            'to_scheduled_at' => 'datetime',
            'to_ends_at' => 'datetime',
            'rescheduled_at' => 'datetime',
            'location_changed' => 'boolean',
            'reminder_already_sent' => 'boolean',
        ];
    }
}
