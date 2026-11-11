<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\User;

/**
 * An HR opinion attached to an evaluation without changing the reviewer's work.
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int $author_user_id
 * @property string $body
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['body'])]
class RecruitmentInterviewEvaluationNote extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentInterviewEvaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(RecruitmentInterviewEvaluation::class, 'evaluation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evaluation notes are append-only.'));
        static::deleting(fn () => throw new LogicException('Evaluation notes are append-only.'));
    }
}
