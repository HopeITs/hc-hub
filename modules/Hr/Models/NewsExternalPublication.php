<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A news item's copy on an external platform (e.g. LinkedIn): status,
 * the platform's id and URL, when it went out, the last error and the
 * attempts. Written only by App\Services\News\Publishing\ExternalPublishing;
 * no platform is integrated yet, so no row is created today.
 *
 * @property int $id
 * @property int $news_id
 * @property string $platform
 * @property string $status
 * @property string|null $external_id
 * @property string|null $external_url
 * @property mixed|null $published_at
 * @property string|null $last_error
 * @property int $attempts
 * @property mixed|null $last_attempt_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['news_id', 'platform', 'status', 'external_id', 'external_url', 'published_at', 'last_error', 'attempts', 'last_attempt_at'])]
class NewsExternalPublication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<News, $this>
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
