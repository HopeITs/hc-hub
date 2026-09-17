<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentInterview extends Model
{
    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewParticipant::class, 'interview_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RecruitmentInterviewEvaluation::class, 'interview_id');
    }

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }
}
