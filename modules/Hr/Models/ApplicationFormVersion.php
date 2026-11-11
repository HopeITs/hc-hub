<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $application_form_id
 * @property int $version_no
 * @property array<mixed> $fields_json
 * @property string|null $privacy_notice_version
 * @property string $status
 * @property mixed|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ApplicationFormVersion extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<ApplicationForm, $this>
     */
    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    /**
     * @return HasMany<PublicRecruitmentLink, $this>
     */
    public function publicLinks(): HasMany
    {
        return $this->hasMany(PublicRecruitmentLink::class, 'form_version_id');
    }

    /**
     * @return HasMany<RecruitmentApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'form_version_id');
    }

    /**
     * @return HasMany<RecruitmentApplicationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationAnswer::class, 'form_version_id');
    }

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'fields_json' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
