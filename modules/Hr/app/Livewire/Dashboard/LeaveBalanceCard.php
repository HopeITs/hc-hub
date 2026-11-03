<?php

namespace Modules\Hr\Livewire\Dashboard;

use App\Livewire\Concerns\HandlesLoadFailure;
use App\Livewire\Concerns\IsDashboardTile;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\LeaveBalance;

/**
 * The viewer's remaining annual leave as a ring. The figures are the annual
 * balance LeaveBalanceService keeps on the viewer's current contract, read
 * through the balance's own accessors (the ones "My leaves" shows): nothing is
 * worked out here but the share of the ring. "Request leave" follows the New
 * menu's rule (leaves.create and leaves.view-own).
 */
class LeaveBalanceCard extends Component
{
    use HandlesLoadFailure;
    use IsDashboardTile;

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.dashboard.leave-balance-card', [
            'balance' => $this->guardedLoad(fn () => $this->annualBalance()),
            'requestUrl' => $user && $user->hasActivePermission('hr.leaves.create') && $user->hasActivePermission('hr.leaves.view-own')
                ? route('leaves.index', ['new' => 1])
                : null,
        ]);
    }

    /** @return array{remaining: int, total: int, share: float}|null */
    protected function annualBalance(): ?array
    {
        $balance = auth()->user()?->profile?->latestActiveContract()
            ?->leaveBalances()
            ->where('type_id', LeaveTypeCases::Annual)
            ->with('contract')
            ->first();

        if (! $balance instanceof LeaveBalance) {
            return null;
        }

        $deserved = (float) ($balance->deserved ?? 0);

        return [
            'remaining' => max(0, $balance->int_remaining),
            'total' => $balance->int_total,
            'share' => $deserved > 0 ? max(0.0, min(1.0, ($deserved - (float) ($balance->used ?? 0)) / $deserved)) : 0.0,
        ];
    }
}
