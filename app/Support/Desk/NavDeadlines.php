<?php

declare(strict_types=1);

namespace App\Support\Desk;

use App\Models\User;
use App\Notifications\Alerts\DeadlineCatalog;
use App\Notifications\Alerts\DeadlineItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Which modules hold something of the viewer's that is due soon or late, so
 * the sidebar can make that group glow. Reads the alert scanners'
 * DeadlineCatalog; the whole catalog is cached for a few minutes because
 * every page load renders the sidebar.
 */
final class NavDeadlines
{
    public const int DaysAhead = 5;

    public const int CacheSeconds = 600;

    public function __construct(private DeadlineCatalog $catalog) {}

    /**
     * @return list<string> module keys, as Destinations names them
     */
    public function modulesFor(User $user): array
    {
        $userId = (int) $user->getKey();

        return collect($this->items())
            ->filter(fn (array $item): bool => in_array($userId, $item['users'], true))
            ->pluck('module')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<array{module: string, users: list<int>}>
     */
    private function items(): array
    {
        $today = CarbonImmutable::today();

        return Cache::remember('nav-deadlines:'.$today->toDateString(), self::CacheSeconds, fn (): array => $this->catalog
            ->items($today->addDays(self::DaysAhead))
            ->map(fn (DeadlineItem $item): array => ['module' => $item->module, 'users' => array_values(array_map('intval', $item->directUserIds))])
            ->values()
            ->all());
    }
}
