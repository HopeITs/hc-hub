<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $job_post_id
 * @property int $version_no
 * @property string $title_en
 * @property string $title_ar
 * @property string|null $body_en
 * @property string|null $body_ar
 * @property array<mixed> $public_payload_json
 * @property string $status
 * @property mixed|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class JobPostVersion extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<JobPost, $this>
     */
    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    /**
     * @return HasMany<PublicRecruitmentLink, $this>
     */
    public function publicLinks(): HasMany
    {
        return $this->hasMany(PublicRecruitmentLink::class, 'job_post_version_id');
    }

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'public_payload_json' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
