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
 * @property string $name
 * @property int $request_type
 * @property bool $is_active
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentWorkflowDefinition extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<RecruitmentWorkflowDefinitionVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowDefinitionVersion::class, 'definition_id');
    }

    /**
     * @return HasOne<RecruitmentWorkflowDefinitionVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(RecruitmentWorkflowDefinitionVersion::class, 'definition_id')
            ->ofMany('version_no', 'max');
    }

    protected function casts(): array
    {
        return [
            'request_type' => 'integer',
            'is_active' => 'boolean'];
    }
}
