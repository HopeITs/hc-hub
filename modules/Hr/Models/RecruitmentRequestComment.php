<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $request_id
 * @property int|null $request_version_id
 * @property int $user_id
 * @property int $comment_type
 * @property string $body
 * @property CarbonImmutable $created_at
 */
class RecruitmentRequestComment extends Model
{
    public const UPDATED_AT = null;

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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'comment_type' => 'integer',
        ];
    }
}
