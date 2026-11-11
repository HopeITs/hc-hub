<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $workflow_instance_id
 * @property int $workflow_step_id
 * @property string $assigned_role
 * @property int|null $assigned_scope_type
 * @property int|null $assigned_scope_id
 * @property int|null $assigned_user_id
 * @property int|null $acted_by_user_id
 * @property int|null $decision
 * @property string|null $decision_reason
 * @property mixed|null $acted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentWorkflowInstanceStep extends Model
{
    protected $guarded = ['id'];

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
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'assigned_scope_type' => 'integer',
            'decision' => 'integer',

            'assigned_scope_id' => 'integer',
            'acted_at' => 'datetime',
        ];
    }
}
