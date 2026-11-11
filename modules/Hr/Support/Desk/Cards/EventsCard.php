<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\News;
use Modules\Hr\Support\News\NewsFeed;

/**
 * Events: today's events first, then the ones of the coming thirty days,
 * among the events addressed to the viewer.
 */
final class EventsCard extends HrCard
{
    public const int Days = 30;

    public function key(): string
    {
        return 'events';
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
        return 'cal';
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        [$today, $upcoming] = $this->events($user);

        return [
            $this->figure(__('hr_desk.today'), $today->count(), 'good'),
            $this->figure(__('hr_desk.cards.events.upcoming'), $upcoming->count(), 'active'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        [$today, $upcoming] = $this->events($user);

        return $today->concat($upcoming)->values()->map(function (News $event) use ($today): array {
            $isToday = $today->contains('id', $event->id);
            $starts = $event->event_starts_at;
            $time = $starts->format('H:i') === '00:00' ? '' : $starts->translatedFormat('H:i');

            return $this->shaped(
                $this->row('ev-'.$event->id, $event->localizedTitle(), trim(implode(' · ', array_filter([$starts->translatedFormat('l'), $time]))), ['label' => $isToday ? (string) __('hr_desk.today') : $this->away($starts), 'tone' => $isToday ? 'good' : 'active'], $this->link('hr.news.show', $event->id), trim($event->title_en.' '.$event->title_ar)),
                lead: $this->day($isToday ? today() : $starts, $isToday ? 'good' : 'active'),
            );
        });
    }

    /**
     * @return array{0: Collection<int, News>, 1: Collection<int, News>}
     */
    private function events(User $user): array
    {
        return once(fn (): array => [app(NewsFeed::class)->todayEvents($user), app(NewsFeed::class)->upcomingEvents($user, self::Days)]);
    }
}
