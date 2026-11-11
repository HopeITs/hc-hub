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
        $employeeId = $this->viewer($user)->employeeId();
        $byType = $employeeId === null ? collect() : Badge::query()
            ->where('employee_id', $employeeId)
            ->whereIn('type', [BadgeType::Reward->value, BadgeType::Warning->value])
            ->groupBy('type')
            ->selectRaw('type, count(*) as badges')
            ->pluck('badges', 'type');

        return [
            $this->figure(__('hr_desk.cards.badges.rewards'), (int) ($byType[BadgeType::Reward->value] ?? 0), 'good'),
            $this->figure(__('hr_desk.cards.badges.warnings'), (int) ($byType[BadgeType::Warning->value] ?? 0), 'warn'),
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
