<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\User;
use Modules\Hr\Enums\RecruitmentFileScanStatus;
use Modules\Hr\Support\Recruitment\Media\HasHrMedia;

/**
 * @property int $id
 * @property int $applicant_id
 * @property int|null $application_id
 * @property string $file_type
 * @property string $scan_status
 * @property mixed $uploaded_at
 * @property mixed|null $archived_at
 * @property int|null $archived_by_user_id
 * @property int|null $archive_note_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property mixed|null $purged_at
 */
class RecruitmentApplicantFile extends Model
{
    use HasHrMedia;

    public const TYPE_CV = 'cv';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplicant, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicant::class);
    }

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    /**
     * @return BelongsTo<RecruitmentApplicantNote, $this>
     */
    public function archiveNote(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicantNote::class, 'archive_note_id')->withTrashed();
    }

    /**
     * @return HasOne<RecruitmentCvAnalysis, $this>
     */
    public function cvAnalysis(): HasOne
    {
        return $this->hasOne(RecruitmentCvAnalysis::class, 'file_id');
    }

    public function isScanClean(): bool
    {
        return $this->scan_status === RecruitmentFileScanStatus::Clean;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isCv(): bool
    {
        return $this->file_type === self::TYPE_CV;
    }

    /**
     * The bytes were removed on purpose (anonymization); the row stays as history.
     */
    public function isPurged(): bool
    {
        return $this->purged_at !== null;
    }

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'archived_at' => 'datetime',
            'purged_at' => 'datetime',
        ];
    }
}
