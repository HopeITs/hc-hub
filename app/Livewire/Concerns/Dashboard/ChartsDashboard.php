<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Dashboard;

use App\Support\Desk\FundingTotals;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * The funding position: money committed against money received, per
 * currency.
 *
 * Used by the Desk's At a glance and Funding position panels and the Ask
 * bar. The host provides canViewFundingPosition() (IsDeskPanel).
 */
trait ChartsDashboard
{
    /**
     * Money committed in funding agreements against money received, per currency.
     *
     * USD, EUR and SYP are not convertible in this system, so each currency is
     * its own line and nothing is ever summed across them.
     *
     * @return Collection<int, array{currency: string, committed: float, received: float, pending: float, pendingCount: int, share: int|null}>
     */
    #[Computed]
    public function fundingPosition(): Collection
    {
        if (! $this->canViewFundingPosition()) {
            return new Collection;
        }

        ['committed' => $committed, 'received' => $received, 'pending' => $pending] = FundingTotals::read();

        return $committed->keys()
            ->concat($received->keys())
            ->concat($pending->keys())
            ->map(fn ($currency): string => (string) $currency)
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $currency) use ($committed, $received, $pending): array {
                $committedTotal = (float) ($committed[$currency] ?? 0);
                $receivedTotal = (float) ($received[$currency] ?? 0);

                return [
                    'currency' => $currency,
                    'committed' => $committedTotal,
                    'received' => $receivedTotal,
                    'pending' => (float) ($pending[$currency]->total ?? 0),
                    'pendingCount' => (int) ($pending[$currency]->transfers ?? 0),
                    // Not capped: money received beyond the commitment must show as over 100%.
                    'share' => $committedTotal > 0 ? (int) floor($receivedTotal / $committedTotal * 100) : null,
                ];
            });
    }
}
