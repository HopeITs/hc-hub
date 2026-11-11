<?php

declare(strict_types=1);

namespace Modules\Hr\Support\News;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\News;

/**
 * The news a reader may see. "Live" is published, released and not past its
 * display date; "released" also keeps what has expired (past events stay
 * readable). Every read is narrowed to the reader's audience (NewsViewer).
 */
final class NewsFeed
{
    /**
     * Columns a list of news needs.
     *
     * @var list<string>
     */
    public const array Columns = ['news.id', 'news.type', 'news.status', 'news.title_en', 'news.title_ar', 'news.description_en', 'news.description_ar', 'news.audience_type', 'news.published_at', 'news.display_until', 'news.event_starts_at', 'news.event_ends_at', 'news.created_by'];

    /**
     * @return Builder<News>
     */
    public function live(?User $user, ?CarbonInterface $now = null): Builder
    {
        return News::query()->select(self::Columns)->live($now)->visibleTo(NewsViewer::targetsOf($user))->with('tags:id,news_id,tag');
    }

    /**
     * @return Builder<News>
     */
    public function released(?User $user, ?CarbonInterface $now = null): Builder
    {
        return News::query()->select(self::Columns)->released($now)->visibleTo(NewsViewer::targetsOf($user))->with('tags:id,news_id,tag');
    }

    /**
     * @return Collection<int, News>
     */
    public function latestNews(?User $user, int $limit = 8): Collection
    {
        return $this->live($user)->articles()->orderByDesc('news.published_at')->limit($limit)->get();
    }

    /**
     * @return Collection<int, News>
     */
    public function todayEvents(?User $user): Collection
    {
        return $this->live($user)->todayEvents()->get();
    }

    /**
     * @return Collection<int, News>
     */
    public function upcomingEvents(?User $user, ?int $days = null, int $limit = 60): Collection
    {
        return $this->live($user)
            ->upcomingEvents()
            ->when($days !== null, fn (Builder $query) => $query->where('news.event_starts_at', '<=', now()->addDays((int) $days)->endOfDay()))
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, News>
     */
    public function eventsBetween(?User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->live($user)
            ->ofType('event')
            ->whereNotNull('news.event_starts_at')
            ->where('news.event_starts_at', '<=', $to->copy()->endOfDay())
            ->where(fn (Builder $query) => $query
                ->where('news.event_starts_at', '>=', $from->copy()->startOfDay())
                ->orWhere('news.event_ends_at', '>=', $from->copy()->startOfDay()))
            ->orderBy('news.event_starts_at')
            ->limit(60)
            ->get();
    }
}
