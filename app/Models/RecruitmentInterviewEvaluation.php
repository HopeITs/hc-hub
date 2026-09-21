<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentInterviewEvaluation extends Model
{
    protected $guarded = ['id'];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(RecruitmentInterview::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }
}
