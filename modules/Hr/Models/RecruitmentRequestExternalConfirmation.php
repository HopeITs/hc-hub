<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $recruitment_request_id
 * @property int|null $step_key
 * @property bool $is_generated
 * @property string|null $confirmer_name
 * @property int|null $confirmer_user_id
 * @property int|null $confirmer_position_id
 * @property int|null $decision
 * @property int|null $method
 * @property mixed|null $confirmed_at
 * @property string|null $not_applicable_reason
 * @property string|null $notes
 * @property int $recorded_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentRequestExternalConfirmation extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'recruitment_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmer_user_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function confirmerPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'confirmer_position_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'step_key' => 'integer',
            'decision' => 'integer',
            'method' => 'integer',

            'is_generated' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }
}
