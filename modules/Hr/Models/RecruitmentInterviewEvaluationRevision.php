<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\User;

/**
 * The evaluation as it stood after one change, with who made it and why.
 * Append-only: a revision is never edited or removed, so no edit — by the
 * reviewer or by HR — can erase what was written before.
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int $version
 * @property string $action
 * @property string|null $pros
 * @property string|null $cons
 * @property string|null $notes
 * @property string $recommendation
 * @property string|null $reason
 * @property int $changed_by_user_id
 * @property mixed $changed_at
 */
#[Hidden(['pros', 'cons', 'notes', 'recommendation', 'reason'])]
class RecruitmentInterviewEvaluationRevision extends Model
{
    public $timestamps = false;

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
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evaluation history is append-only.'));
        static::deleting(fn () => throw new LogicException('Evaluation history is append-only.'));
    }

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
