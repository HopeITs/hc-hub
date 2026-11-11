<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One free tag of a news item ("Aleppo", "Study Zone", "Training"): what the
 * item is about. Tags never decide who sees an item (that is the audience).
 * Written only through News::syncTags(), which normalizes them.
 *
 * @property int $id
 * @property int $news_id
 * @property string $tag
 */
#[Fillable(['news_id', 'tag'])]
class NewsTag extends Model
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
