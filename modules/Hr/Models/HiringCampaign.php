<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\User;
use Modules\Hr\Enums\HiringCampaignStatus;

/**
 * @property int $id
 * @property int $vacancy_id
 * @property int $status
 * @property string $link_mode
 * @property int|null $minimum_age
 * @property int|null $maximum_age
 * @property mixed|null $starts_at
 * @property mixed|null $ends_at
 * @property int $created_by_user_id
 * @property int|null $closed_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class HiringCampaign extends Model
{
    /**
     * Who a campaign is open to.
     *
     * The three run in sequence on one seat rather than at once: the post goes
     * round the organisation first, then out to everybody, and sometimes to a
     * named list — the students of one of our own programmes, say — whose
     * names and addresses are entered in advance.
     *
     * `link_mode` is a plain string column with nothing behind it, so these
     * three spellings are the whole vocabulary. `public_open` is the schema's
     * own default and is fixed; the other two are named here.
     */
    public const INTERNAL = 'internal';

    public const PUBLIC_OPEN = 'public_open';

    /**
     * Spelled as the public-link boundary has always spelled it. Early drafts
     * stored `invite_only`; the accessor below reads that as this value, so a
     * campaign created before the two vocabularies were unified still enforces
     * its invitation list.
     */
    public const INVITE_ONLY = 'invitation_only';

    public const LEGACY_INVITE_ONLY = 'invite_only';

    /**
     * @var array<int, string>
     */
    public const LINK_MODES = [self::INTERNAL, self::PUBLIC_OPEN, self::INVITE_ONLY];

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * @return HasOne<JobPost, $this>
     */
    public function jobPost(): HasOne
    {
        return $this->hasOne(JobPost::class, 'campaign_id');
    }

    /**
     * @return HasOne<ApplicationForm, $this>
     */
    public function applicationForm(): HasOne
    {
        return $this->hasOne(ApplicationForm::class, 'campaign_id');
    }

    /**
     * @return HasMany<PublicRecruitmentLink, $this>
     */
    public function publicLinks(): HasMany
    {
        return $this->hasMany(PublicRecruitmentLink::class, 'campaign_id');
    }

    /**
     * @return HasMany<RecruitmentCampaignInvite, $this>
     */
    public function invites(): HasMany
    {
        return $this->hasMany(RecruitmentCampaignInvite::class, 'campaign_id');
    }

    /**
     * @return HasMany<RecruitmentApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'campaign_id');
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
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * Campaigns that still occupy the vacancy's current advertising cycle.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where(function (Builder $ongoing): void {
            $ongoing
                ->where('status', HiringCampaignStatus::Draft->value)
                ->orWhere(function (Builder $published): void {
                    $published
                        ->whereIn('status', [
                            HiringCampaignStatus::Published->value,
                            HiringCampaignStatus::Open->value,
                        ])
                        ->where(function (Builder $window): void {
                            $window->whereNull('ends_at')->orWhere('ends_at', '>', now());
                        });
                });
        });
    }

    /**
     * Only a named list may apply; everybody else is turned away.
     */
    public function isInvitationOnly(): bool
    {
        return $this->link_mode === self::INVITE_ONLY;
    }

    /**
     * Staff only: applicants sign in to HC-HR and apply as themselves.
     */
    public function isInternal(): bool
    {
        return $this->link_mode === self::INTERNAL;
    }

    /**
     * Reads the legacy spelling as the canonical one; see INVITE_ONLY.
     *
     * @return Attribute<string|null, never>
     */
    protected function linkMode(): Attribute
    {
        return Attribute::get(fn (?string $value): ?string => $value === self::LEGACY_INVITE_ONLY
            ? self::INVITE_ONLY
            : $value);
    }

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'minimum_age' => 'integer',
            'maximum_age' => 'integer',
            'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
