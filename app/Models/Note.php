<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NoteVisibility;
use Carbon\CarbonImmutable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\User;

/**
 * A note on a record (subject set) or a personal note (no subject). The one
 * Notes capability every module shares; see NotePolicy for who reads what.
 *
 * @property int $id
 * @property int $author_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $body
 * @property NoteVisibility $visibility
 * @property string|null $color
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['author_id', 'subject_type', 'subject_id', 'body', 'visibility', 'color'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use SoftDeletes;

    public const int MaxLength = 1000;

    /**
     * The most notes one author keeps on one record (or in their notebook).
     */
    public const int MaxPerAuthor = 100;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visibility' => 'private',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => NoteVisibility::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The record the note is about; null for a personal note.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The personal notes of one user: no subject, theirs.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function personalFor(Builder $query, int $userId): void
    {
        $query->where('author_id', $userId)->whereNull('subject_type')->whereNull('subject_id');
    }

    /**
     * Notes about one record.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function about(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey());
    }
}
