<?php

namespace App\Models;

use App\Services\RecruitmentScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    protected $guarded = ['id'];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function createdFromRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'created_from_request_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(HiringCampaign::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(RecruitmentJobOffer::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(RecruitmentScopeResolver::class)->scopeVacancies($query, $user);
    }

    protected function casts(): array
    {
        return [
            'needed_count' => 'integer',
            'filled_count' => 'integer',
            'open_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
