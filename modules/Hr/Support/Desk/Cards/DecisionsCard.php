<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Support\Inbox\WorkInbox;

/**
 * HR decisions: what waits on the viewer in the HR inbox (leaves to decide
 * or review, items to hand over, profile changes, journeys), the longest
 * wait first. Each row opens the record's own page.
 */
final class DecisionsCard extends HrCard
{
    public function key(): string
    {
        return 'decisions';
    }

    public function allows(User $user): bool
    {
        return app(WorkInbox::class)->hasSources($user);
    }

    public function register(): ?string
    {
        return 'hr.inbox';
    }

    public function icon(): string
    {
        return 'flag';
    }

    public function size(): string
    {
        return 'L';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        $counts = app(WorkInbox::class)->for($user)['counts'];

        return [
            $this->figure(__('hr_desk.cards.decisions.decide'), $counts['decide'], 'critical'),
            $this->figure(__('hr_desk.cards.decisions.review'), $counts['review'] + $counts['profile'], 'warn'),
            $this->figure(__('hr_desk.cards.decisions.other'), $counts['checklist'] + $counts['journey']),
        ];
    }

    public function health(User $user): ?string
    {
        $inbox = app(WorkInbox::class)->for($user);

        return match (true) {
            $inbox['rows']->contains('urgent', true) => 'critical',
            $inbox['counts']['all'] > 0 => 'brand',
            default => null,
        };
    }

    protected function records(User $user, string $search): Collection
    {
        return app(WorkInbox::class)->for($user)['rows']->take(self::RowLimit)->map(function (array $row): array {
            $waited = $row['since']?->diffForHumans(null, true) ?? '';
            $dates = $row['from'] === null ? '' : $this->span($row['from'], $row['to']);

            return $this->shaped(
                $this->row($row['key'], $row['name'], trim(implode(' · ', array_filter([$row['what'], $dates, $row['city']]))), ['label' => (string) __('hr_communications.inbox.action.'.$row['action']), 'tone' => $row['urgent'] ? 'critical' : ($row['source'] === 'leave' ? 'warn' : 'neutral')], $row['href'] !== '' ? $row['href'] : $this->link('hr.inbox'), $row['words']),
                lead: $this->face((int) $row['employee_id'], $row['name']),
                end: $waited === '' ? null : ['big' => $waited],
            );
        })->values();
    }
}
