<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RecruitmentApplicantFile extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicant::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->useDisk('local')
            ->singleFile();
    }

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }
}
