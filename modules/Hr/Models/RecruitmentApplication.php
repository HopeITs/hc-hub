<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * @property int $id
 * @property int $applicant_id
 * @property int $campaign_id
 * @property int $vacancy_id
 * @property int $form_version_id
 * @property int $public_link_id
 * @property int|null $campaign_invite_id
 * @property string|null $submission_key_hash
 * @property string $email_normalized
 * @property string $status
 * @property string|null $privacy_notice_version
 * @property mixed|null $privacy_accepted_at
 * @property array<mixed>|null $consent_payload_json
 * @property mixed $submitted_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['submission_key_hash'])]
class RecruitmentApplication extends Model
{
    protected $guarded = ['id'];

    private static bool $applyingDecision = false;

    /**
     * @internal RecruitmentApplicationStatusService only, inside its
     *           transaction and after it has validated the transition.
     */
    public static function applyDecision(self $application, string $status): void
    {
        self::$applyingDecision = true;

        try {
            $application->forceFill(['status' => $status])->save();
        } finally {
            self::$applyingDecision = false;
        }
    }

    /**
     * @return BelongsTo<RecruitmentApplicant, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicant::class);
    }

    /**
     * @return BelongsTo<HiringCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(HiringCampaign::class);
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * @return BelongsTo<ApplicationFormVersion, $this>
     */
    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(ApplicationFormVersion::class, 'form_version_id');
    }

    /**
     * @return BelongsTo<PublicRecruitmentLink, $this>
     */
    public function publicLink(): BelongsTo
    {
        return $this->belongsTo(PublicRecruitmentLink::class, 'public_link_id');
    }

    /**
     * @return BelongsTo<RecruitmentCampaignInvite, $this>
     */
    public function campaignInvite(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCampaignInvite::class, 'campaign_invite_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationAnswer::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicantFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(RecruitmentApplicantFile::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicantFile, $this>
     */
    public function cvFiles(): HasMany
    {
        return $this->files()->where('file_type', RecruitmentApplicantFile::TYPE_CV);
    }

    /**
     * @return HasMany<RecruitmentApplicationTag, $this>
     */
    public function tagAssignments(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationTag::class, 'application_id');
    }

    /**
     * @return BelongsToMany<RecruitmentTag, $this, RecruitmentApplicationTag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            RecruitmentTag::class,
            'recruitment_application_tags',
            'application_id',
            'tag_id',
        )->using(RecruitmentApplicationTag::class)
            ->withPivot(['id', 'created_by_user_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<RecruitmentApplicationFlag, $this>
     */
    public function flags(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationFlag::class, 'application_id');
    }

    /**
     * @return HasOne<RecruitmentApplicationIndicatorSnapshot, $this>
     */
    public function indicatorSnapshot(): HasOne
    {
        return $this->hasOne(RecruitmentApplicationIndicatorSnapshot::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicantNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(RecruitmentApplicantNote::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationDecision::class, 'application_id');
    }

    /**
     * @return HasOne<RecruitmentRejectionNotice, $this>
     */
    public function rejectionNotice(): HasOne
    {
        return $this->hasOne(RecruitmentRejectionNotice::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationEndorsement, $this>
     */
    public function endorsements(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationEndorsement::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationParticipant, $this>
     */
    public function reviewParticipants(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationParticipant::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationParticipant, $this>
     */
    public function currentReviewParticipants(): HasMany
    {
        return $this->reviewParticipants()->current();
    }

    /**
     * @return HasMany<RecruitmentApplicationReview, $this>
     */
    public function profileReviews(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationReview::class, 'application_id');
    }

    /**
     * @return HasMany<RecruitmentInterview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(RecruitmentInterview::class, 'application_id');
    }

    /**
     * @return HasOne<RecruitmentJobOffer, $this>
     */
    public function jobOffer(): HasOne
    {
        return $this->hasOne(RecruitmentJobOffer::class, 'application_id');
    }

    /**
     * Status is written in exactly one place. Any other save that changes it —
     * a mass assignment from request data, a stray forceFill — is refused
     * here, so a decision can never happen without its history row.
     */
    protected static function booted(): void
    {
        static::updating(function (self $application): void {
            if ($application->isDirty('status') && ! self::$applyingDecision) {
                throw new LogicException('An application status changes only through RecruitmentApplicationStatusService.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'consent_payload_json' => 'array',
            'privacy_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
