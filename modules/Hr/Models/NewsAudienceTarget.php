<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One city/center/program/department/employee id in a news item's audience (kind = news.audience_type).
 *
 * @property int $id
 * @property int $news_id
 * @property int $target_id
 */
#[Fillable(['news_id', 'target_id'])]
class NewsAudienceTarget extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<News, $this>
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
