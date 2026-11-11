<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentInterviewStatus;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\Media\HasHrMedia;
use Modules\Hr\Support\Recruitment\PositionRequester;

/**
 * One interview of one application (Phase 2 Task 7). An application can have
 * any number of them — an HR screening, a technical one, a final one.
 *
 * Written only by RecruitmentInterviewSchedulingService, which validates each
 * status move, keeps the reschedule history and queues the candidate emails.
 * Interviews are never deleted: delete() refuses, and "gone" is Cancelled.
 *
 * meeting_url is hidden from serialization: it reaches a page only through
 * the viewMeetingLink ability, never by riding along in a Livewire payload.
 *
 * @property int $id
 * @property int $application_id
 * @property string $interview_type
 * @property string $status
 * @property mixed $scheduled_at
 * @property mixed|null $ends_at
 * @property string|null $timezone
 * @property string $location_type
 * @property string|null $location_value
 * @property string|null $meeting_url
 * @property mixed|null $slot_set_at
 * @property int $reschedule_count
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property mixed|null $cancelled_at
 * @property int|null $cancelled_by_user_id
 * @property string|null $cancellation_reason
 * @property string|null $cancellation_note
 * @property mixed|null $outcome_recorded_at
 * @property int|null $outcome_recorded_by_user_id
 * @property string|null $outcome_note
 * @property int|null $updated_by_user_id
 * @property mixed|null $evaluations_closed_at
 * @property int|null $evaluations_closed_by_user_id
 */
#[Hidden(['meeting_url'])]
class RecruitmentInterview extends Model
{
    use HasHrMedia;

    public const ATTACHMENTS = 'attachments';

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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function outcomeRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'outcome_recorded_by_user_id');
    }

    /**
     * Everyone ever put on this interview, including those taken off it.
     *
     * @return HasMany<RecruitmentInterviewParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewParticipant::class, 'interview_id');
    }

    /**
     * The people currently on it.
     *
     * @return HasMany<RecruitmentInterviewParticipant, $this>
     */
    public function currentParticipants(): HasMany
    {
        return $this->participants()->whereNull('removed_at');
    }

    /**
     * @return HasMany<RecruitmentInterviewRequiredParticipant, $this>
     */
    public function requiredParticipants(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewRequiredParticipant::class, 'interview_id');
    }

    /**
     * @return HasMany<RecruitmentInterviewEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewEvaluation::class, 'interview_id');
    }

    /**
     * @return HasMany<RecruitmentInterviewReschedule, $this>
     */
    public function reschedules(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewReschedule::class, 'interview_id');
    }

    /**
     * @return HasMany<RecruitmentInterviewNotice, $this>
     */
    public function notices(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewNotice::class, 'interview_id');
    }

    public function isOpen(): bool
    {
        return RecruitmentInterviewStatus::isOpen($this->status);
    }

    public function isParticipant(User $user): bool
    {
        return $this->currentParticipants()->where('user_id', $user->getKey())->exists();
    }

    /** Did this user request the position the interview is for (PositionRequester)? */
    public function isRequestedBy(User $user): bool
    {
        return static::query()->whereKey($this->getKey())->requestedBy($user)->exists();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function evaluationsClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluations_closed_by_user_id');
    }

    /**
     * Task 8: closed by HR after completion, or — when the policy sets
     * recruitment.evaluations.auto_close_days — that many days after the
     * outcome was recorded. Once closed, reviewers can no longer edit; HR can
     * still correct.
     */
    public function evaluationsClosed(): bool
    {
        if ($this->evaluations_closed_at !== null) {
            return true;
        }

        $days = config('recruitment.evaluations.auto_close_days');

        return $days !== null
            && $this->status === RecruitmentInterviewStatus::Completed
            && $this->outcome_recorded_at !== null
            && $this->outcome_recorded_at->copy()->addDays((int) $days)->isPast();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn($query->qualifyColumn('status'), RecruitmentInterviewStatus::open());
    }

    /**
     * Still ahead, or under way right now: open and not yet at its end time.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->open()->where(fn (Builder $ahead): Builder => $ahead
            ->where($query->qualifyColumn('ends_at'), '>', now())
            ->orWhere(fn (Builder $noEnd): Builder => $noEnd
                ->whereNull($query->qualifyColumn('ends_at'))
                ->where($query->qualifyColumn('scheduled_at'), '>', now())));
    }

    /**
     * Took place, or its time is over: completed, or past its end without an outcome yet.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeHeldOrPast(Builder $query): Builder
    {
        return $query
            ->whereNotIn($query->qualifyColumn('status'), [RecruitmentInterviewStatus::Cancelled, RecruitmentInterviewStatus::NoShow])
            ->where(fn (Builder $past): Builder => $past
                ->where($query->qualifyColumn('status'), RecruitmentInterviewStatus::Completed)
                ->orWhere(fn (Builder $ended): Builder => $ended
                    ->whereNotNull($query->qualifyColumn('ends_at'))
                    ->where($query->qualifyColumn('ends_at'), '<=', now()))
                ->orWhere(fn (Builder $started): Builder => $started
                    ->whereNull($query->qualifyColumn('ends_at'))
                    ->where($query->qualifyColumn('scheduled_at'), '<=', now())));
    }

    /**
     * The query twin of evaluationsClosed(): closed by HR, or by the auto-close policy.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeEvaluationsClosed(Builder $query, bool $closed = true): Builder
    {
        $days = config('recruitment.evaluations.auto_close_days');

        $condition = function (Builder $closedQuery) use ($query, $days): void {
            $closedQuery->whereNotNull($query->qualifyColumn('evaluations_closed_at'));

            if ($days !== null) {
                $closedQuery->orWhere(fn (Builder $auto): Builder => $auto
                    ->where($query->qualifyColumn('status'), RecruitmentInterviewStatus::Completed)
                    // Not-null first, so the negated form never meets a NULL comparison.
                    ->whereNotNull($query->qualifyColumn('outcome_recorded_at'))
                    ->where($query->qualifyColumn('outcome_recorded_at'), '<', now()->subDays((int) $days)));
            }
        };

        return $closed ? $query->where($condition) : $query->whereNot($condition);
    }

    /**
     * Task 8: the interviews waiting on this user's own evaluation — they sit
     * on it in a reviewer role, HR recorded it as held, evaluation is still
     * open, and they have not written one yet.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAwaitingEvaluationBy(Builder $query, User $user): Builder
    {
        return $query
            ->whereHas('currentParticipants', fn (Builder $participants): Builder => $participants
                ->where('user_id', $user->getKey())
                ->whereIn('role', (array) config('recruitment.evaluations.reviewer_roles', [])))
            ->heldOrPast()
            ->evaluationsClosed(false)
            ->whereDoesntHave('evaluations', fn (Builder $evaluations): Builder => $evaluations->where('reviewer_user_id', $user->getKey()));
    }

    /**
     * For the sidebar: is this user on any interview at all?
     */
    public static function hasAnyFor(User $user): bool
    {
        return RecruitmentInterviewParticipant::query()
            ->where('user_id', $user->getKey())
            ->whereNull('removed_at')
            ->exists();
    }

    /**
     * The deleting event in booted() does not fire for a query-level delete
     * (RecruitmentInterview::query()->where(...)->delete()), so the Eloquent
     * builder refuses that too. Only a raw DB::table() call bypasses both, and
     * the restrict foreign keys from the reschedule and notice history stop it
     * for any interview that was ever moved or emailed about.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function delete()
            {
                throw new LogicException('Interviews are never deleted; cancel them through RecruitmentInterviewSchedulingService.');
            }

            public function forceDelete()
            {
                $this->delete();
            }

            public function truncate(): void
            {
                $this->delete();
            }
        };
    }

    protected static function booted(): void
    {
        // "Do not delete an interview instead of cancelling it" — enforced
        // here so no future screen or script can do it by accident.
        static::deleting(function (): void {
            throw new LogicException('Interviews are never deleted; cancel them through RecruitmentInterviewSchedulingService.');
        });
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'slot_set_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'outcome_recorded_at' => 'datetime',
            'evaluations_closed_at' => 'datetime',
            'reschedule_count' => 'integer',
        ];
    }

    /**
     * What a user may see: interviews they are currently on, every interview
     * of a position they requested, plus — for anyone who can read
     * applicants — every interview of an application in their recruitment
     * scope. The three add up; none takes away from another.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visible) use ($user): void {
            $visible->whereHas(
                'currentParticipants',
                fn (Builder $participants): Builder => $participants->where('user_id', $user->getKey()),
            );

            $visible->orWhere(fn (Builder $requested): Builder => $requested->requestedBy($user));

            if (Access::hasActivePermission($user, 'recruitment-applicants.view')) {
                $visible->orWhereHas(
                    'application',
                    fn (Builder $applications): Builder => app(RecruitmentScopeResolver::class)->scopeApplications($applications, $user),
                );
            }
        });
    }

    /**
     * Interviews of a position this user requested (PositionRequester). He
     * follows them read-only; what he may do is the policy's call.
     */
    public function scopeRequestedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('application.vacancy.createdFromRequest', fn (Builder $requests): Builder => PositionRequester::constrain($requests, $user));
    }
}
