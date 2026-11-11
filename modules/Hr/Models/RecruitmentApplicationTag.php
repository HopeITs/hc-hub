<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $application_id
 * @property int $tag_id
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentApplicationTag extends Pivot
{
    public $incrementing = true;

    protected $table = 'recruitment_application_tags';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    /**
     * @return BelongsTo<RecruitmentTag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(RecruitmentTag::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
