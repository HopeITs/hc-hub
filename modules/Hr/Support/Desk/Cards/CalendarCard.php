<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\News;
use Modules\Hr\Support\Desk\TeamLeaves;
use Modules\Hr\Support\Desk\UpcomingHolidays;
use Modules\Hr\Support\News\NewsFeed;

/**
 * HR calendar: the coming thirty days as one agenda — the holidays that
 * reach the viewer, the events addressed to them, their own leaves (accepted
 * or still waiting), their team's accepted leaves and birthdays, and, for
 * whoever oversees contracts, the contracts ending. One read per kind; the
 * team and the contracts follow the same rules as the Upcoming leaves,
 * Birthdays and Ending contracts cards.
 */
final class CalendarCard extends HrCard
{
    public const int Days = 30;

    public function key(): string
    {
        return 'calendar';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function register(): ?string
    {
        return 'hr.team.index';
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function size(): string
    {
        return 'L';
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        $items = $this->items($user);

        return [
            $this->figure(__('hr_desk.cards.calendar.kind.holiday'), $items->where('kind', 'holiday')->count(), 'good'),
            $this->figure(__('hr_desk.cards.calendar.kind.event'), $items->where('kind', 'event')->count(), 'active'),
            $this->figure(__('hr_desk.cards.calendar.kind.leave'), $items->where('kind', 'leave')->count(), 'warn'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->items($user)->map(fn (array $item): array => $this->shaped(
            [...$this->row($item['key'], $item['title'], $item['meta'], ['label' => (string) __('hr_desk.cards.calendar.kind.'.$item['kind']), 'tone' => $item['tone']], $item['href']), 'kind' => (string) __('hr_desk.cards.calendar.kind.'.$item['kind'])],
            lead: $this->day($item['from']->lt(today()) ? today() : $item['from'], $item['tone']),
        ));
    }

    /**
     * @return Collection<int, array{key: string, kind: string, title: string, meta: string, tone: string, from: CarbonImmutable, href: string}>
     */
    private function items(User $user): Collection
    {
        return once(function () use ($user): Collection {
            $viewer = $this->viewer($user);
            $today = CarbonImmutable::today();
            $until = $today->addDays(self::Days);

            $holidays = app(UpcomingHolidays::class)->for($viewer->programId(), $viewer->cityId(), self::Days)->map(fn (array $holiday): array => [
                'key' => 'cl-h-'.$holiday['id'].'-'.$holiday['from']->toDateString(),
                'kind' => 'holiday',
                'title' => $holiday['name'],
                'meta' => $this->span($holiday['from'], $holiday['to']),
                'tone' => 'good',
                'from' => $holiday['from'],
                'href' => $this->link('hr.leave.holidays.index'),
            ]);

            $events = app(NewsFeed::class)->eventsBetween($user, $today, $until)->map(fn (News $event): array => [
                'key' => 'cl-e-'.$event->id,
                'kind' => 'event',
                'title' => $event->localizedTitle(),
                'meta' => $this->span($event->event_starts_at, $event->event_ends_at),
                'tone' => 'active',
                'from' => CarbonImmutable::instance($event->event_starts_at),
                'href' => $this->link('hr.news.show', $event->id),
            ]);

            $leaves = $viewer->employeeId() === null ? collect() : app(TeamLeaves::class)
                ->between([$viewer->employeeId()], $today, $until, [LeaveRequestStatus::Accepted, LeaveRequestStatus::Pending])
                ->map(fn (LeaveRequest $leave): array => [
                    'key' => 'cl-l-'.$leave->id,
                    'kind' => 'leave',
                    'title' => (string) ($leave->leaveBalance?->leaveType?->name ?? '—'),
                    'meta' => $this->span($leave->start_at, $leave->end_at).' · '.__('hr_desk.leave_status.'.(int) $leave->status),
                    'tone' => 'warn',
                    'from' => CarbonImmutable::instance($leave->start_at),
                    'href' => $this->link('hr.leave.requests.index'),
                ]);

            $teamLeaves = app(TeamLeaves::class)
                ->between($viewer->othersIds(), $today, $until)
                ->map(function (LeaveRequest $leave): array {
                    $employee = TeamLeaves::employeeOf($leave);

                    return [
                        'key' => 'cl-t-'.$leave->id,
                        'kind' => 'team-leave',
                        'title' => (string) ($employee?->name ?? '—'),
                        'meta' => $this->span($leave->start_at, $leave->end_at).' · '.($leave->leaveBalance?->leaveType?->name ?? ''),
                        'tone' => 'neutral',
                        'from' => CarbonImmutable::instance($leave->start_at),
                        'href' => $this->link('hr.team.index'),
                    ];
                });

            $birthdays = app(BirthdaysCard::class)->within($user, self::Days + 1)->map(fn (array $person): array => [
                'key' => 'cl-b-'.$person['id'],
                'kind' => 'birthday',
                'title' => $person['name'],
                'meta' => CarbonImmutable::parse($person['date'])->translatedFormat('l j M'),
                'tone' => 'good',
                'from' => CarbonImmutable::parse($person['date']),
                'href' => $this->link('hr.team.index'),
            ]);

            $endings = app(EndingContractsCard::class);
            $contracts = ! $endings->allows($user) ? collect() : $endings->contracts($user)->map(fn (Contract $contract): array => [
                'key' => 'cl-c-'.$contract->id,
                'kind' => 'contract',
                'title' => (string) ($contract->employee?->name ?? '—'),
                'meta' => $contract->end_date->translatedFormat('j M Y'),
                'tone' => 'critical',
                'from' => CarbonImmutable::instance($contract->end_date),
                'href' => $this->link('hr.contracts.show', $contract->id) ?: $this->link('hr.contracts.index'),
            ]);

            return collect($holidays)->concat($events)->concat($leaves)->concat($teamLeaves)->concat($birthdays)->concat($contracts)
                ->sortBy(fn (array $item): int => $item['from']->getTimestamp())
                ->values();
        });
    }
}
