<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $interview_id
 * @property string $seat
 * @property int $owner_user_id
 * @property int $attendee_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentInterviewRequiredParticipant extends Model
{
    public const SEAT_HR = 'hr';

    public const SEAT_MANAGER = 'manager';

    protected $guarded = ['id'];

    /**
     * @return array<int, string>
     */
    public static function seats(): array
    {
        return [self::SEAT_HR, self::SEAT_MANAGER];
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
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendee_user_id');
    }
}
