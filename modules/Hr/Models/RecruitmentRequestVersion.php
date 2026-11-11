<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $request_id
 * @property int $version_no
 * @property array<mixed> $snapshot_json
 * @property int $created_by_user_id
 * @property mixed $submitted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentRequestVersion extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'request_id');
    }

    /**
     * @return HasOne<RecruitmentRequestPositionDetail, $this>
     */
    public function positionDetail(): HasOne
    {
        return $this->hasOne(RecruitmentRequestPositionDetail::class, 'request_version_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<RecruitmentRequestComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(RecruitmentRequestComment::class, 'request_version_id');
    }

    /**
     * @return HasOne<RecruitmentWorkflowInstance, $this>
     */
    public function workflowInstance(): HasOne
    {
        return $this->hasOne(RecruitmentWorkflowInstance::class, 'request_version_id');
    }

    /**
     * @return HasMany<RecruitmentRequestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(RecruitmentRequestEvent::class, 'request_version_id');
    }

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'snapshot_json' => 'array',
            'submitted_at' => 'datetime',
        ];
    }
}
