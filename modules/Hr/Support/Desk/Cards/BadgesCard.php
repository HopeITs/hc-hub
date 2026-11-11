<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;

/**
 * My badges: the rewards, warnings and salary increases on the viewer's own
 * record, newest first. Amounts and salaries are never printed on the Desk.
 */
final class BadgesCard extends HrCard
{
    public function key(): string
    {
        return 'badges';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function icon(): string
    {
        return 'flag';
    }

    public function size(): string
    {
        return 'S';
    }

    public function layout(): string
    {
        return 'tagged';
    }

    public function figures(User $user): array
    {
        $badges = $this->mine($user);

        return [
            $this->figure(__('hr_desk.cards.badges.rewards'), $badges->where('type', (string) BadgeType::Reward->value)->count(), 'good'),
            $this->figure(__('hr_desk.cards.badges.warnings'), $badges->where('type', (string) BadgeType::Warning->value)->count(), 'warn'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->mine($user)->map(function (Badge $badge): array {
            $type = (int) $badge->type;
            $tone = match ($type) {
                BadgeType::Reward->value, BadgeType::Salary_Increase->value => 'good',
                BadgeType::Warning->value => 'warn',
                default => 'neutral',
            };

            return $this->row('bd-'.$badge->id, BadgeType::getLocalizedName($type), trim((string) $badge->reason), ['label' => (string) ($badge->date?->translatedFormat('j M Y') ?? ''), 'tone' => $tone], '');
        });
    }

    /**
     * @return Collection<int, Badge>
     */
    private function mine(User $user): Collection
    {
        $employeeId = $this->viewer($user)->employeeId();

        if ($employeeId === null) {
            return new Collection;
        }

        return once(fn (): Collection => Badge::query()
            ->select(['id', 'employee_id', 'type', 'subtype', 'date', 'reason'])
            ->where('employee_id', $employeeId)
            ->orderByDesc('date')
            ->limit(self::RowLimit)
            ->get());
    }
}
