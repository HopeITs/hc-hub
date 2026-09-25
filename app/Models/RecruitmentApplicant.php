<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentApplicant extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['identity_key_hash'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** Set only when the applicant signed in as staff to apply. */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isInternal(): bool
    {
        return $this->employee_id !== null;
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'applicant_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(RecruitmentApplicantFile::class, 'applicant_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RecruitmentApplicantNote::class, 'applicant_id');
    }

    public function hiringConversions(): HasMany
    {
        return $this->hasMany(RecruitmentHiringConversion::class, 'applicant_id');
    }

    protected function casts(): array
    {
        return [
            'identity_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'retention_until' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }
}
