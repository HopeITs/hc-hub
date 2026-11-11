<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Contracts\PositionSeats;

/**
 * End a contract early: the date and reason are recorded, the contract goes
 * inactive (so it can no longer auto-renew) and its seat is released.
 */
class TerminateContract
{
    /**
     * @param  array<string, mixed>  $data  terminated_at, termination_reason_id, reason_of_termination
     */
    public function handle(Contract $contract, array $data, User $actor): Contract
    {
        Gate::forUser($actor)->authorize('update', $contract);

        Validator::make($data, [
            'terminated_at' => ['required', 'date', 'after_or_equal:'.$contract->start_date?->toDateString()],
            'termination_reason_id' => ['required', 'integer', Rule::exists('termination_reasons', 'id')],
            'reason_of_termination' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'terminated_at' => __('hr_contracts.fields.terminated_at'),
            'termination_reason_id' => __('hr_contracts.fields.termination_reason_id'),
            'reason_of_termination' => __('hr_contracts.fields.reason_of_termination'),
        ])->validate();

        return DB::transaction(function () use ($contract, $data): Contract {
            $held = PositionSeats::holdsSeat($contract->type, $contract->is_active);

            $contract->forceFill([
                'terminated_at' => $data['terminated_at'],
                'termination_reason_id' => (int) $data['termination_reason_id'],
                'reason_of_termination' => filled($data['reason_of_termination'] ?? null) ? $data['reason_of_termination'] : null,
                'is_active' => false,
                'auto_renew' => false,
            ])->save();

            if ($held) {
                PositionSeats::move($contract->position_id, null);
            }

            return $contract;
        });
    }
}
