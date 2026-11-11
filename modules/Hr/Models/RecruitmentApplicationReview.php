<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $application_id
 * @property int $reviewer_user_id
 * @property string $recommendation
 * @property string|null $pros
 * @property string|null $cons
 * @property string|null $notes
 * @property int $version
 * @property mixed $submitted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['pros', 'cons', 'notes', 'recommendation'])]
class RecruitmentApplicationReview extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
