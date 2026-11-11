<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\News;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Enums\News\NewsState;
use Modules\Hr\Enums\News\NewsStatus;
use Modules\Hr\Models\News;
use Modules\Hr\Support\News\NewsCover;

/**
 * The one-step changes of a news item from its list: publish now, take back
 * to draft, archive, restore and delete. Each is allowed only from the states
 * the old HR app allowed it from; anything else leaves the item as it is and
 * answers false.
 */
final class ChangeNewsState
{
    public function publishNow(User $actor, News $news): bool
    {
        return $this->from($news, [NewsState::Draft, NewsState::Scheduled])
            && $this->save($actor, $news, ['status' => NewsStatus::Published, 'published_at' => now(), 'archived_at' => null]);
    }

    public function unpublish(User $actor, News $news): bool
    {
        return $this->from($news, [NewsState::Published, NewsState::Scheduled, NewsState::Expired])
            && $this->save($actor, $news, ['status' => NewsStatus::Draft]);
    }

    public function archive(User $actor, News $news): bool
    {
        return ! $this->from($news, [NewsState::Archived])
            && $this->save($actor, $news, ['status' => NewsStatus::Archived, 'archived_at' => now()]);
    }

    /**
     * Back from the archive: published again if it had been released, else a draft.
     */
    public function restore(User $actor, News $news): bool
    {
        return $this->from($news, [NewsState::Archived])
            && $this->save($actor, $news, ['status' => $news->published_at !== null ? NewsStatus::Published : NewsStatus::Draft, 'archived_at' => null]);
    }

    public function delete(News $news): void
    {
        DB::transaction(function () use ($news): void {
            $news->audienceTargets()->delete();
            $news->tags()->delete();
            NewsCover::remove((int) $news->id);
            $news->delete();
        });
    }

    /**
     * @param  list<NewsState>  $states
     */
    private function from(News $news, array $states): bool
    {
        return in_array($news->state(), $states, true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function save(User $actor, News $news, array $attributes): bool
    {
        $news->forceFill([...$attributes, 'updated_by' => $actor->id])->save();

        return true;
    }
}
