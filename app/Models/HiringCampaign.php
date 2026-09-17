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
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
