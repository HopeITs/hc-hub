<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Modules\Hr\Models\News;
use Modules\Hr\Support\News\NewsFeed;

/**
 * News: the latest news and announcements addressed to the viewer, newest
 * first. Everyone has it; what it lists follows each item's audience.
 */
final class NewsCard extends HrCard
{
    public const int Limit = 8;

    public function key(): string
    {
        return 'news';
    }

    public function allows(User $user): bool
    {
        return true;
    }

    public function register(): ?string
    {
        return 'hr.news.index';
    }

    public function icon(): string
    {
        return 'bell';
    }

    public function size(): string
    {
        return 'L';
    }

    public function layout(): string
    {
        return 'feed';
    }

    public function figures(User $user): array
    {
        $news = $this->latest($user);

        return [
            $this->figure(__('hr_desk.cards.news.this_week'), $news->filter(fn (News $item): bool => $item->published_at?->gte(now()->subWeek()) === true)->count(), 'active'),
            $this->figure(__('hr_desk.cards.news.showing'), $news->count()),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->latest($user)->map(fn (News $item): array => $this->shaped(
            $this->row('nw-'.$item->id, $item->localizedTitle(), Str::limit(trim(strip_tags((string) $item->localizedDescription())), 110), ['label' => (string) __('hr_news.types.'.$item->type->value), 'tone' => $item->type->value === 'announcement' ? 'warn' : 'active'], $this->link('hr.news.show', $item->id), trim($item->title_en.' '.$item->title_ar)),
            lead: $item->published_at === null ? null : $this->day($item->published_at, 'active'),
        ));
    }

    /**
     * @return Collection<int, News>
     */
    private function latest(User $user): Collection
    {
        return once(fn (): Collection => app(NewsFeed::class)->latestNews($user, self::Limit));
    }
}
