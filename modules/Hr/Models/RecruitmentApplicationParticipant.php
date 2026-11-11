<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $application_id
 * @property int|null $applicant_id
 * @property int $user_id
 * @property string $role
 * @property int $added_by_user_id
 * @property mixed|null $removed_at
 * @property int|null $removed_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentApplicationParticipant extends Model
{
    public const ROLE_REVIEWER = 'reviewer';

    public const ROLE_VIEWER = 'viewer';

    protected $guarded = ['id'];

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [self::ROLE_REVIEWER, self::ROLE_VIEWER];
    }

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    /**
     * @return BelongsTo<RecruitmentApplicant, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicant::class, 'applicant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id');
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
