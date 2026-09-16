<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentJobOfferVersion extends Model
{
    protected $guarded = ['id'];

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJobOffer::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function hiringConversions(): HasMany
    {
        return $this->hasMany(RecruitmentHiringConversion::class, 'job_offer_version_id');
    }

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'salary_amount' => 'integer',
            'currency' => 'integer',
            'salary_per' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'weekly_hours' => 'integer',
            'probation_days' => 'integer',
            'terms_json' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
