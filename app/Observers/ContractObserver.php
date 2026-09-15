<?php

namespace App\Observers;

use App\Enums\ContractType as ContractTypeEnum;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Position;
use App\Support\StatisticsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractObserver
{
    public function creating(Contract $contract): void
    {
        // Auto-renew defaults true (right for volunteer and service
        // contracts) except for trial/probation contracts, which default
        // false — a trial is meant to end and be evaluated, not roll over
        // silently. Only applied when the caller left it unset, so an
        // explicit true/false (e.g. from the composer, once it exposes this
        // field) is never overridden.
        if (! array_key_exists('auto_renew', $contract->getAttributes())) {
            $contract->auto_renew = ContractType::find($contract->contract_type_id)?->category !== 'trial';
        }

        // A draft contract (a placeholder position/center reservation
        // created from the employee-create screen) does not claim a seat
        // yet: no capacity check here, and created() below skips the
        // matching inc(). The seat is claimed only when the draft is
        // upgraded to a real type — see updating()/updated().
        if ((int) $contract->type === ContractTypeEnum::Draft) {
            return;
        }

        if (app()->runningInConsole()) {
            return;
        }

        // A renewal onto the same position is the same person keeping the same
        // seat, so it is exempt from the capacity check. exist_emp still rises
        // by one for the overlap between issuing the renewal and the source
        // expiring, and `deactivate:contract` brings it back down from there.
        if ($contract->isRenewalInPlace()) {
            return;
        }

        $pos = Position::query()
            ->whereKey($contract->position_id)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $pos->canAssign()) {
            throw ValidationException::withMessages([
                'form.position_id' => __('validation.custom.position_id.max_reached'),
            ]);
        }
    }

    public function created(Contract $contract): void
    {
        if ((int) $contract->type === ContractTypeEnum::Draft) {
            return;
        }

        $pos = Position::query()
            ->whereKey($contract->position_id)
            ->lockForUpdate()
            ->first();

        if ($pos) {
            $pos->inc();
        }
    }

    public function updating(Contract $contract): void
    {
        // Upgrading a draft to a real type claims the seat now, exactly as
        // if this were the contract's first creation: check capacity here,
        // and updated() below applies the matching inc(). No decrement is
        // needed on any "old" position, because a draft never incremented
        // one in the first place.
        if ($this->isUpgradingFromDraft($contract)) {
            $pos = Position::query()
                ->whereKey($contract->position_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $pos->canAssign()) {
                throw ValidationException::withMessages([
                    'form.position_id' => __('validation.custom.position_id.max_reached'),
                ]);
            }

            return;
        }

        // A contract that stays a draft carries no capacity bookkeeping at all.
        if ((int) $contract->type === ContractTypeEnum::Draft) {
            return;
        }

        if (! $contract->isDirty('position_id')) {
            return;
        }

        $oldId = (int) $contract->getOriginal('position_id');
        $newId = (int) $contract->position_id;

        [$a, $b] = $oldId < $newId ? [$oldId, $newId] : [$newId, $oldId];

        $locked = Position::query()
            ->whereIn('id', [$a, $b])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $new = $locked[$newId] ?? null;
        if (! $new) {
            throw ValidationException::withMessages([
                'form.position_id' => __('validation.exists', ['attribute' => 'position']),
            ]);
        }
        if (! $new->canAssign()) {
            throw ValidationException::withMessages([
                'form.position_id' => __('validation.custom.position_id.max_reached'),
            ]);
        }
    }

    public function updated(Contract $contract): void
    {
        if ($this->wasUpgradedFromDraft($contract)) {
            $pos = Position::query()
                ->whereKey($contract->position_id)
                ->lockForUpdate()
                ->first();

            if ($pos) {
                $pos->inc();
            }

            return;
        }

        if ((int) $contract->type === ContractTypeEnum::Draft) {
            return;
        }

        if (! $contract->wasChanged('position_id')) {
            return;
        }

        $oldId = (int) $contract->getOriginal('position_id');
        $newId = (int) $contract->position_id;

        [$a, $b] = $oldId < $newId ? [$oldId, $newId] : [$newId, $oldId];

        $locked = Position::query()
            ->whereIn('id', [$a, $b])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $old = $locked[$oldId] ?? null;
        $new = $locked[$newId] ?? null;

        if ($old) {
            $old->dec();
        }
        if ($new) {
            $new->inc();
        }
    }

    /** Is $contract's pending write the transition from Draft to a real type? */
    private function isUpgradingFromDraft(Contract $contract): bool
    {
        return (int) $contract->getOriginal('type') === ContractTypeEnum::Draft
            && (int) $contract->type !== ContractTypeEnum::Draft;
    }

    /** Did the write that just completed transition $contract from Draft to a real type? */
    private function wasUpgradedFromDraft(Contract $contract): bool
    {
        return $contract->wasChanged('type')
            && (int) $contract->getOriginal('type') === ContractTypeEnum::Draft
            && (int) $contract->type !== ContractTypeEnum::Draft;
    }

    public function deleted(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $pos = Position::query()
                ->whereKey($contract->position_id)
                ->lockForUpdate()
                ->first();

            if ($pos && $pos->exist_emp > 0) {
                $pos->decrement('exist_emp');
            }
        });

        StatisticsCache::flush();
    }

    /**
     * Every dashboard aggregate is derived from contracts, so any write to this
     * table invalidates all of them.
     *
     * `saved` deliberately covers both insert and update in one hook rather than
     * duplicating the call in created() and updated() — a future code path that
     * saves a contract some other way inherits the invalidation for free.
     * Deletion is handled separately above because `saved` does not fire for it.
     */
    public function saved(Contract $contract): void
    {
        StatisticsCache::flush();
    }
}
