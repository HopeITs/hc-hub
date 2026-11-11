<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A staff member on an interview. Taking someone off sets removed_at rather
 * than deleting the row, so the record of who was on it survives — and so
 * does any evaluation they already wrote (Task 8).
 *
 * @property int $id
 * @property int $interview_id
 * @property int $user_id
 * @property string $role
 * @property string $notification_status
 * @property int|null $added_by_user_id
 * @property mixed|null $removed_at
 * @property int|null $removed_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentInterviewParticipant extends Model
{
    public const ROLE_INTERVIEWER = 'interviewer';

    public const ROLE_OBSERVER = 'observer';

    public const NOTIFICATION_PENDING = 'pending';

    public const NOTIFICATION_SENT = 'sent';

    public const NOTIFICATION_FAILED = 'failed';

    /**
     * The user's account or employee record is no longer active.
     */
    public const NOTIFICATION_SKIPPED = 'skipped';

    protected $guarded = ['id'];

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [self::ROLE_INTERVIEWER, self::ROLE_OBSERVER];
    }

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('removed_at'));
    }

    protected function casts(): array
    {
        return ['removed_at' => 'datetime'];
    }
}
