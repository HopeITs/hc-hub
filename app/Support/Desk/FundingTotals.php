<?php

declare(strict_types=1);

namespace App\Support\Desk;

use Illuminate\Support\Collection;
use Modules\Tracking\Enums\ArStatus;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\TransfersTracking;

/**
 * The three money totals behind the funding position (ChartsDashboard), read
 * once per request: At a glance, Funding position and the Ask bar all show
 * them, and a Desk refresh renders those panels in the same request. once()
 * keys by the calling class, so the totals live here rather than in the trait
 * each panel uses. They are the same for every viewer; who may see them is
 * checked by the caller.
 */
final class FundingTotals
{
    /**
     * @return array{committed: Collection<string, mixed>, received: Collection<string, mixed>, pending: Collection<string, object>}
     */
    public static function read(): array
    {
        return once(static fn (): array => [
            'committed' => DonorProposal::query()
                ->selectRaw('currency, sum(budget) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency'),
            'received' => TransfersTracking::query()
                ->where('ar_status', ArStatus::Received)
                ->selectRaw('amount_received_currency as currency, sum(net_amount_received) as total')
                ->groupBy('amount_received_currency')
                ->pluck('total', 'currency'),
            'pending' => TransfersTracking::query()
                ->where('ar_status', ArStatus::Pending)
                ->selectRaw('amount_transfered_currency as currency, sum(payment_amount) as total, count(*) as transfers')
                ->groupBy('amount_transfered_currency')
                ->get()
                ->keyBy('currency'),
        ]);
    }
}
