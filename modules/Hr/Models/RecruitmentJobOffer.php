<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\RecruitmentJobOfferStatus;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\Media\HasHrMedia;
use Modules\Hr\Support\Recruitment\PositionRequester;

/**
 * The job offer for one selected application (Phase 2 Task 9). One per
 * application (unique key): a revised offer is a new version of it, never a
 * second offer, so an application can never have two live offers.
 *
 * Written only by RecruitmentJobOfferService, which validates each status
 * move, keeps every version, and hands an accepted offer to the hiring
 * conversion exactly once. Never deleted: "gone" is Cancelled.
 *
 * @property int $id
 * @property int $application_id
 * @property int $vacancy_id
 * @property int $position_id
 * @property string $status
 * @property int|null $current_version_id
 * @property int $created_by_user_id
 * @property mixed|null $sent_at
 * @property mixed|null $accepted_at
 * @property mixed|null $rejected_at
 * @property int|null $response_recorded_by_user_id
 * @property string|null $response_note
 * @property mixed|null $expires_at
 * @property mixed|null $expired_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property mixed|null $cancelled_at
 * @property int|null $cancelled_by_user_id
 * @property string|null $cancellation_reason
 * @property string|null $cancellation_note
 * @property int|null $updated_by_user_id
 */
#[Hidden(['response_note', 'cancellation_note'])]
class RecruitmentJobOffer extends Model
{
    use HasHrMedia;

    public const CANCEL_APPLICATION_CLOSED = 'application_closed';

    public const RESPONSE_EVIDENCE = 'response_evidence';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<RecruitmentJobOfferVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJobOfferVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<RecruitmentJobOfferVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(RecruitmentJobOfferVersion::class, 'job_offer_id');
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
    public function responseRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'response_recorded_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * @return HasOne<RecruitmentHiringConversion, $this>
     */
    public function hiringConversion(): HasOne
    {
        return $this->hasOne(RecruitmentHiringConversion::class, 'job_offer_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, RecruitmentJobOfferStatus::editable(), true);
    }

    /**
     * A query-level delete skips model events, so the builder refuses it too.
     */
    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder
        {
            public function delete()
            {
                throw new LogicException('Job offers are never deleted; cancel them through RecruitmentJobOfferService.');
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
        static::deleting(function (): void {
            throw new LogicException('Job offers are never deleted; cancel them through RecruitmentJobOfferService.');
        });
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Offers visible to a user: HR who can read offers, for applications in
     * their recruitment scope; and the manager who requested the position,
     * read-only. The recruitment scope alone is not enough — for a manager it
     * also covers his direct reports' requests.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $readsOffers = Access::hasActivePermission($user, 'recruitment-applicants.view-offers')
            && Access::hasActivePermission($user, 'recruitment-applicants.view');

        return $query->where(fn (Builder $visible): Builder => $visible
            ->when($readsOffers, fn (Builder $hr): Builder => $hr->whereHas('application', fn (Builder $applications): Builder => $applications->visibleTo($user)))
            ->orWhere(fn (Builder $requested): Builder => $requested->requestedBy($user)));
    }

    /** Offers of a position this user requested (PositionRequester). */
    public function scopeRequestedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('vacancy.createdFromRequest', fn (Builder $requests): Builder => PositionRequester::constrain($requests, $user));
    }
}
