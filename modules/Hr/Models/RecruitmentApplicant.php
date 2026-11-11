<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;

/**
 * @property int $id
 * @property string $full_name
 * @property string $email_normalized
 * @property string|null $phone_normalized
 * @property string|null $phone_match_key
 * @property string|null $identity_key_hash
 * @property int|null $possible_duplicate_of_applicant_id
 * @property mixed|null $identity_verified_at
 * @property int|null $city_id
 * @property int|null $employee_id
 * @property string $source
 * @property mixed|null $last_seen_at
 * @property mixed|null $retention_until
 * @property mixed|null $anonymized_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $privacy_notice_version
 * @property mixed|null $privacy_accepted_at
 * @property mixed|null $erasure_requested_at
 * @property string|null $anonymization_reason
 */
#[Hidden(['identity_key_hash'])]
class RecruitmentApplicant extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Set only when the applicant signed in as staff to apply.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isInternal(): bool
    {
        return $this->employee_id !== null;
    }

    /**
     * @return HasMany<RecruitmentApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'applicant_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationParticipant, $this>
     */
    public function reviewParticipants(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationParticipant::class, 'applicant_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationParticipant, $this>
     */
    public function currentReviewParticipants(): HasMany
    {
        return $this->reviewParticipants()->current();
    }

    /**
     * @return HasMany<RecruitmentApplicantFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(RecruitmentApplicantFile::class, 'applicant_id');
    }

    /**
     * @return HasMany<RecruitmentApplicantNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(RecruitmentApplicantNote::class, 'applicant_id');
    }

    /**
     * @return HasMany<RecruitmentHiringConversion, $this>
     */
    public function hiringConversions(): HasMany
    {
        return $this->hasMany(RecruitmentHiringConversion::class, 'applicant_id');
    }

    /**
     * Known sources are translated; anything else is shown as stored, made readable.
     */
    public static function sourceLabel(?string $source): string
    {
        if (blank($source)) {
            return '—';
        }

        $key = 'recruitment-applicant.sources.'.$source;

        return __($key) === $key ? str($source)->headline()->toString() : __($key);
    }

    /**
     * The earlier applicant sharing this phone under another email — a hint, never a merge.
     *
     * @return BelongsTo<self, $this>
     */
    public function possibleDuplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'possible_duplicate_of_applicant_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function possibleDuplicates(): HasMany
    {
        return $this->hasMany(self::class, 'possible_duplicate_of_applicant_id');
    }

    protected function casts(): array
    {
        return [
            'identity_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'retention_until' => 'datetime',
            'anonymized_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'erasure_requested_at' => 'datetime',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $scopes = app(RecruitmentScopeResolver::class);

        // An organization-wide scope adds no SQL constraint. Returning here
        // prevents the empty nested group below from narrowing the result to
        // explicitly assigned participants only.
        if ($scopes->isOrganizationWide($user)) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user): void {
            app(RecruitmentScopeResolver::class)->scopeApplicants($visible, $user);
            $visible->orWhereHas('currentReviewParticipants', fn (Builder $participants): Builder => $participants
                ->where('user_id', $user->getKey()));
        });
    }
}
