<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;

/**
 * @property int $id
 * @property int $recruitment_request_id
 * @property int|null $request_version_id
 * @property int $event_type
 * @property int|null $workflow_instance_id
 * @property int|null $workflow_step_id
 * @property int|null $from_status
 * @property int|null $to_status
 * @property int|null $decision
 * @property int|null $actor_user_id
 * @property string $actor_role
 * @property int|null $scope_type
 * @property int|null $scope_id
 * @property string|null $comment
 * @property array<mixed>|null $meta
 * @property CarbonImmutable $created_at
 */
class RecruitmentRequestEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'recruitment_request_id');
    }

    /**
     * @return BelongsTo<RecruitmentRequestVersion, $this>
     */
    public function requestVersion(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequestVersion::class, 'request_version_id');
    }

    /**
     * @return BelongsTo<RecruitmentWorkflowInstance, $this>
     */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(RecruitmentWorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * @return BelongsTo<RecruitmentWorkflowStep, $this>
     */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(RecruitmentWorkflowStep::class, 'workflow_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected function casts(): array
    {
        return [
            'event_type' => 'integer',
            'from_status' => 'integer',
            'to_status' => 'integer',
            'decision' => 'integer',
            'scope_type' => 'integer',

            'scope_id' => 'integer',
            'meta' => 'array',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(RecruitmentScopeResolver::class)->scopeRequestEvents($query, $user);
    }
}
