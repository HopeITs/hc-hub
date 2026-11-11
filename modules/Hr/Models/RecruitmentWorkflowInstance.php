<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $request_id
 * @property int $request_version_id
 * @property int $definition_version_id
 * @property int $status
 * @property int $current_step_order
 * @property mixed $started_at
 * @property mixed|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentWorkflowInstance extends Model
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
     * @return BelongsTo<RecruitmentRequestVersion, $this>
     */
    public function requestVersion(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequestVersion::class, 'request_version_id');
    }

    /**
     * @return BelongsTo<RecruitmentWorkflowDefinitionVersion, $this>
     */
    public function definitionVersion(): BelongsTo
    {
        return $this->belongsTo(RecruitmentWorkflowDefinitionVersion::class, 'definition_version_id');
    }

    /**
     * @return HasMany<RecruitmentWorkflowInstanceStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowInstanceStep::class, 'workflow_instance_id');
    }

    /**
     * @return HasMany<RecruitmentRequestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(RecruitmentRequestEvent::class, 'workflow_instance_id');
    }

    protected function casts(): array
    {
        return [
            'status' => 'integer',

            'current_step_order' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
