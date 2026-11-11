<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $application_id
 * @property string $from_status
 * @property string $to_status
 * @property string $reason_code
 * @property string|null $reason_text
 * @property int $decided_by_user_id
 * @property mixed $decided_at
 * @property CarbonImmutable $created_at
 */
class RecruitmentApplicationDecision extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }
}
