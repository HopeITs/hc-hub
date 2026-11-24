<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Contracts\PositionSeats;
use Modules\Hr\Support\Leave\LeaveBalanceService;
use Throwable;

/**
 * The nightly auto-renewal (HR's ContractAutoRenewalService::process()): a
 * contract that ended, was switched inactive by `contracts:deactivate-expired`
 * and still has auto-renew on gets one successor, starting the day after it
 * ended and running to the end of the year.
 *
 * The successor claims its seat through PositionSeats (the source gave its own
 * back when it was deactivated), without a capacity check: it is the same
 * person keeping the same seat. Saving it through Eloquent lets JourneyHooks
 * see it. Switching auto-renew on or off is ToggleAutoRenew, not this class.
 */
class ContractAutoRenewalService
{
    public function __construct(private LeaveBalanceService $leaveBalances) {}

    /**
     * Does this contract wait for the new year? A contract ending in one of
     * config('contracts.renew_at_new_year_months') (October to December) is
     * not renewed the day after it ends, which made renewals of a few days
     * up to 31 December: it is renewed on 1 January, for the whole year.
     */
    public static function waitsForNewYear(Contract $contract): bool
    {
        return $contract->end_date !== null
            && in_array($contract->end_date->month, (array) config('contracts.renew_at_new_year_months', []), true)
            && $contract->end_date->format('m-d') !== '12-31';
    }

    /**
     * The day the renewal of $contract starts: 1 January after it for a
     * contract that waits for the new year, otherwise the day after it ends.
     * The one rule the renewal run, the renewal journey and the departure
     * check (OffboardingJourneys::awaitsAutoRenewal) all read.
     */
    public static function renewalStart(Contract $contract): ?CarbonInterface
    {
        if ($contract->end_date === null) {
            return null;
        }

        return self::waitsForNewYear($contract)
            ? $contract->end_date->copy()->addYear()->startOfYear()
            : $contract->end_date->copy()->addDay();
    }

    /**
     * Ended, set to auto-renew, and waiting for the new year: the contracts
     * city HR is told about (ProcessContractAutoRenewalsCommand) so the gap
     * until 1 January is not a surprise.
     *
     * @return Collection<int, Contract>
     */
    public function waitingForNewYear(?CarbonInterface $asOf = null): Collection
    {
        $asOf ??= today();

        return $this->renewable($asOf)
            ->with('employee')
            ->get()
            ->filter(fn (Contract $contract): bool => $contract->canAutoRenew()
                && self::waitsForNewYear($contract)
                && self::renewalStart($contract)->toDateString() > $asOf->toDateString())
            ->values();
    }

    /**
     * @return Collection<int, Contract>
     */
    public function process(?CarbonInterface $asOf = null): Collection
    {
        $asOf ??= today();
        /** @var Collection<int, Contract> $renewed */
        $renewed = collect();

        $this->renewable($asOf)
            ->select(['id'])
            ->chunkById(100, function (Collection $contracts) use ($renewed, $asOf): void {
                foreach ($contracts as $contract) {
                    $renewal = $this->renewIfNeeded((int) $contract->id, $asOf);

                    if ($renewal !== null) {
                        $renewed->push($renewal);
                    }
                }
            });

        return $renewed;
    }

    /**
     * Idempotent: a contract that already has a successor starting the day
     * after it ended is left alone.
     */
    public function renewIfNeeded(int $contractId, CarbonInterface $asOf): ?Contract
    {
        return DB::transaction(function () use ($contractId, $asOf): ?Contract {
            $source = Contract::query()->with('payTiers')->whereKey($contractId)->lockForUpdate()->first();

            if (
                $source === null
                || ! $source->auto_renew
                || ! $source->canAutoRenew()
                || $source->is_active
                || $source->end_date === null
                || $source->end_date->gte($asOf)
            ) {
                return null;
            }

            $start = self::renewalStart($source)->toDateString();

            // Ended in October to December: renewed on 1 January, not before.
            if ($start > $asOf->toDateString()) {
                return null;
            }

            if ($this->successorExists($source, $start)) {
                return null;
            }

            $renewal = $source->replicate(['id', 'created_at', 'updated_at']);
            $renewal->forceFill([
                'start_date' => $start,
                'end_date' => $asOf->copy()->endOfYear()->toDateString(),
                'terminated_at' => null,
                'termination_reason_id' => null,
                'reason_of_termination' => null,
                'is_active' => true,
                'auto_renew' => true,
            ])->save();

            if (PositionSeats::holdsSeat($renewal->type, true)) {
                PositionSeats::move(null, (int) $renewal->position_id);
            }

            foreach ($source->payTiers as $tier) {
                $renewal->payTiers()->create($tier->only(['condition_en', 'condition_ar', 'amount', 'sort_order']));
            }

            $this->createLeaveBalances($renewal);

            return $renewal->refresh();
        });
    }

    /**
     * Ended before $asOf, inactive, auto-renew on and never terminated.
     *
     * @return Builder<Contract>
     */
    private function renewable(CarbonInterface $asOf): Builder
    {
        return Contract::query()
            ->where('auto_renew', true)
            ->where('is_active', false)
            ->whereNull('terminated_at')
            ->whereNull('termination_reason_id')
            ->whereDate('end_date', '<', $asOf->toDateString());
    }

    /**
     * The renewal is a new contract, so it gets its own leave balances. Inside
     * a savepoint: a balance that cannot be priced (no annual-leave rule on
     * file) is reported and leaves the renewal standing, so one bad row never
     * blocks the whole night's run.
     */
    private function createLeaveBalances(Contract $renewal): void
    {
        try {
            DB::transaction(fn () => $this->leaveBalances->create($renewal));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function successorExists(Contract $source, string $startDate): bool
    {
        return Contract::query()
            ->whereKeyNot($source->id)
            ->where('employee_id', $source->employee_id)
            ->where('position_id', $source->position_id)
            ->whereDate('start_date', $startDate)
            ->exists();
    }
}
