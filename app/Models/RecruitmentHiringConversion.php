<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentHiringConversion extends Model
{
    protected $guarded = ['id'];

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJobOffer::class);
    }

    public function jobOfferVersion(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJobOfferVersion::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'employee_payload_json' => 'array',
            'attempt_count' => 'integer',
            'started_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }
}
