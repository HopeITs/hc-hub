<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractObserver
{
    public function creating(Contract $contract): void
    {
        if (app()->runningInConsole() || app()->environment('local')) {
            return;
        }

        $pos = \App\Models\Position::query()
            ->whereKey($contract->position_id)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $pos->canAssign()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.position_id' => __('validation.custom.position_id.max_reached'),
            ]);
        }
    }


    public function created(Contract $contract): void
    {
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

        if ($old) $old->dec();
        if ($new) $new->inc();
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
    }

}
