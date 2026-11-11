<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $definition_version_id
 * @property int $step_order
 * @property int $step_key
 * @property string $role_key
 * @property int $scope_rule
 * @property array<mixed>|null $required_condition_json
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentWorkflowStep extends Model
{
    protected $guarded = ['id'];

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
    public function instanceSteps(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowInstanceStep::class, 'workflow_step_id');
    }

    /**
     * @return HasMany<RecruitmentRequestEvent, $this>
     */
    public function requestEvents(): HasMany
    {
        return $this->hasMany(RecruitmentRequestEvent::class, 'workflow_step_id');
    }

    protected function casts(): array
    {
        return [
            'step_key' => 'integer',
            'scope_rule' => 'integer',

            'step_order' => 'integer',
            'required_condition_json' => 'array',
        ];
    }
}
