<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentCampaignInvite extends Model
{
    protected $guarded = ['id'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(HiringCampaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function application(): HasOne
    {
        return $this->hasOne(RecruitmentApplication::class, 'campaign_invite_id');
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
