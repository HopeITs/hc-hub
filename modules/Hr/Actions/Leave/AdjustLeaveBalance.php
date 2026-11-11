<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Models\User;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Support\Leave\LeaveNotificationService;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Add hours or days to an annual balance, or take them away (the old
 * LeaveBalanceForm::updateAnnualLeaves). A day is the contract's working day
 * (weekly hours ÷ 5, as before). Optionally recorded as a reward or warning
 * badge on the employee, which then needs a note and badges.create. The
 * employee and their direct manager are told.
 */
final class AdjustLeaveBalance
{
    public function __construct(private readonly LeaveNotificationService $notifications) {}

    /**
     * @param  array{operation?: mixed, unit?: mixed, amount?: mixed, add_badge?: mixed, notes?: mixed}  $input
     */
    public function handle(User $actor, int $balanceId, array $input): LeaveBalance
    {
        $balance = LeaveBalance::query()
            ->with('contract:id,employee_id,weekly_hours', 'contract.employee:id,city_id')
            ->findOrFail($balanceId);

        Gate::forUser($actor)->authorize('update', $balance);

        $addBadge = filter_var($input['add_badge'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data = Validator::make(
            ['operation' => $input['operation'] ?? null, 'unit' => $input['unit'] ?? null, 'amount' => $input['amount'] ?? null, 'notes' => trim((string) ($input['notes'] ?? ''))],
            [
                'operation' => ['required', 'in:sum,sub'],
                'unit' => ['required', 'in:hour,day'],
                'amount' => ['required', 'numeric', 'min:0', 'max:10000'],
                'notes' => [$addBadge ? 'required' : 'nullable', 'string', 'max:5000'],
            ],
            [],
            __('hr_leave.balance.fields'),
        )->validate();

        $employee = $balance->contract?->employee;

        if ($addBadge && ! ($actor->can('badges.create') && $this->inScope($actor, $employee?->city_id))) {
            throw new AuthorizationException;
        }

        $amount = (float) $data['amount'];
        $hours = $data['unit'] === 'day' ? $amount * ((float) ($balance->contract?->weekly_hours ?? 0) / 5) : $amount;
        $hours = $data['operation'] === 'sum' ? $hours : -$hours;

        DB::transaction(function () use ($balance, $hours, $addBadge, $data, $employee): void {
            $locked = LeaveBalance::query()->lockForUpdate()->findOrFail($balance->id);
            $locked->update(['deserved' => (int) round((float) $locked->deserved + $hours)]);

            if ($addBadge && $employee !== null) {
                Badge::query()->create([
                    'employee_id' => $employee->id,
                    'type' => $data['operation'] === 'sum' ? BadgeType::Reward->value : BadgeType::Warning->value,
                    'date' => now(),
                    'notes' => $data['notes'],
                ]);
            }
        });

        $unitLabel = trans_choice('hr_leave.balance.units.'.$data['unit'], (int) ceil($amount), ['count' => $amount]);
        $this->notifications->sendBalanceChange($balance, $actor, (string) $data['operation'], $unitLabel, $data['notes'] === '' ? null : $data['notes']);

        return $balance->refresh();
    }

    private function inScope(User $actor, ?int $cityId): bool
    {
        $cityIds = HrAccess::for($actor)->cityIds();

        return $cityIds === null || ($cityId !== null && in_array($cityId, $cityIds, true));
    }
}
