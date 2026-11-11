<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $definition_id
 * @property int $version_no
 * @property array<mixed> $rules_json
 * @property bool $is_active
 * @property mixed|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentWorkflowDefinitionVersion extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentWorkflowDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(RecruitmentWorkflowDefinition::class, 'definition_id');
    }

    /**
     * @return HasMany<RecruitmentWorkflowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowStep::class, 'definition_version_id')
            ->orderBy('step_order');
    }

    /**
     * @return HasMany<RecruitmentWorkflowInstance, $this>
     */
    public function workflowInstances(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowInstance::class, 'definition_version_id');
    }

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'rules_json' => 'array',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
