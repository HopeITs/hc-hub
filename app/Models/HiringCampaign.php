<?php

namespace App\Models;

use App\Services\RecruitmentScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public const INVITE_ONLY = 'invite_only';

    /** @var array<int, string> */
    public const LINK_MODES = [self::INTERNAL, self::PUBLIC_OPEN, self::INVITE_ONLY];

    protected $guarded = ['id'];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function jobPost(): HasOne
    {
        return $this->hasOne(JobPost::class, 'campaign_id');
    }

    public function applicationForm(): HasOne
    {
        return $this->hasOne(ApplicationForm::class, 'campaign_id');
    }

    public function publicLinks(): HasMany
    {
        return $this->hasMany(PublicRecruitmentLink::class, 'campaign_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(RecruitmentCampaignInvite::class, 'campaign_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(RecruitmentScopeResolver::class)->scopeCampaigns($query, $user);
    }

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
