<?php

namespace App\Models;

use App\Enums\RecruitmentFileScanStatus;
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
            ->useDisk(config('recruitment-uploads.quarantine_disk'))
            ->singleFile();
    }

    public function isScanClean(): bool
    {
        return $this->scan_status === RecruitmentFileScanStatus::Clean;
    }

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }
}
