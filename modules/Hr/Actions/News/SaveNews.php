<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\News;

use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Enums\News\AudienceType;
use Modules\Hr\Enums\News\NewsStatus;
use Modules\Hr\Enums\News\NewsType;
use Modules\Hr\Models\News;
use Modules\Hr\Support\News\NewsCover;

/**
 * Creates or updates a news item from validated form values.
 *
 * Saving as a draft keeps a chosen publish time for later. Publishing sets
 * the publish time to the chosen one, or keeps the original time of an item
 * that is already out, or else now. An event's times are kept only for
 * events. The audience, the tags and the cover are saved in the same
 * transaction as the item.
 */
final class SaveNews
{
    public const string Draft = 'draft';

    public const string Publish = 'publish';

    /**
     * @param  array{type: NewsType, title_ar: string, title_en: string|null, description_ar: string, description_en: string|null, tags: list<string>, audience_type: AudienceType, audience_ids: list<int>, publish_at: CarbonInterface|null, event_starts_at: CarbonInterface|null, event_ends_at: CarbonInterface|null, display_until: CarbonInterface|null}  $values
     */
    public function handle(User $actor, ?News $news, array $values, string $intent, ?UploadedFile $cover = null): News
    {
        return DB::transaction(function () use ($actor, $news, $values, $intent, $cover): News {
            $isNew = $news === null;
            $news ??= new News;

            if ($isNew) {
                $news->created_by = $actor->id;
            } else {
                $news->updated_by = $actor->id;
            }

            if ($intent === self::Draft) {
                $news->status = NewsStatus::Draft;
                $news->published_at = $values['publish_at'];
            } else {
                $alreadyOut = ! $isNew && $news->status === NewsStatus::Published && $news->published_at !== null && $news->published_at->lte(now());
                $news->status = NewsStatus::Published;
                $news->published_at = $values['publish_at'] ?? ($alreadyOut ? $news->published_at : now());
                $news->archived_at = null;
            }

            $isEvent = $values['type'] === NewsType::Event;

            $news->fill([
                'type' => $values['type'],
                'title_ar' => $values['title_ar'],
                'title_en' => $values['title_en'] ?: null,
                'description_ar' => $values['description_ar'],
                'description_en' => $values['description_en'] ?: null,
                'event_starts_at' => $isEvent ? $values['event_starts_at'] : null,
                'event_ends_at' => $isEvent ? $values['event_ends_at'] : null,
                'display_until' => $values['display_until'],
            ]);

            if (! $isEvent) {
                $news->event_date = null;
            }

            $news->syncAudience($values['audience_type'], $values['audience_ids']);
            $news->syncTags($values['tags']);

            if ($cover !== null) {
                NewsCover::store((int) $news->id, $cover);
            }

            return $news;
        });
    }
}
