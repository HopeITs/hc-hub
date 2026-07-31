<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\LeaveType as LeaveTypeEnum;
use App\Enums\LeaveTypeCases;
use App\Enums\MaritalStatus;
use App\Models\AnnualLeaveRule;
use App\Models\Contract;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;

/**
 * Generates and reconciles the leave balances a contract is entitled to.
 *
 * This is the single source of truth for leave-balance calculation, shared by
 * the contract create/edit forms (LeaveBalanceForm) and the leaves:backfill
 * console command. Keep all entitlement logic here — not duplicated elsewhere.
 */
class LeaveBalanceService
{
    /**
     * Create the full set of leave balances for a brand-new contract.
     */
    public function create(Contract $contract): void
    {
        $contract->load(['employee', 'contractType']);
        $employee = $contract->employee->load('contracts', 'allContracts');
        $leaveRule = $this->getAnnualLeaveRule($employee);
        $leaveTypes = LeaveType::whereNot('base_days', 12)->get();

        $dailyWorkHours = $contract->work_hour_per_day;
        $extra = $this->calculateExtraLeave($employee, $contract);

        [$yearly, $sick, $unpaid] = $this->calculateDeservedLeaves($contract->duration, $leaveRule, $dailyWorkHours, $extra);

        // Annual leave
        LeaveBalance::create([
            'contract_id' => $contract->id,
            'type_id' => LeaveTypeCases::Annual,
            'deserved' => $yearly,
        ]);

        // Skip if in trial period
        if (in_array($contract->contract_type_id, [3, 4])) {
            return;
        }

        // Common leave types
        $this->createCommonLeaves($contract, $sick, $unpaid);

        // Gender and marital-specific leaves
        $this->createSpecificLeaves($employee, $contract, $leaveTypes, $dailyWorkHours);
    }

    /**
     * Recalculate and reconcile the leave balances of an existing contract.
     */
    public function update(Contract $contract): void
    {
        $contract->load(['employee', 'contractType', 'leaveBalances']);
        $employee = $contract->employee;
        $leaveRule = $this->getAnnualLeaveRule($employee);
        $leaveTypes = LeaveType::whereNot('base_days', 12)->get();

        $dailyWorkHours = $contract->work_hour_per_day;
        $extra = $this->calculateExtraLeave($employee, $contract);

        [$yearly, $sick, $unpaid] = $this->calculateDeservedLeaves($contract->duration, $leaveRule, $dailyWorkHours, $extra);

        // Annual
        $contract->leaveBalances->where('type_id', LeaveTypeCases::Annual)->first()?->update(['deserved' => $yearly]);

        // Skip if in trial period
        if (in_array($contract->contract_type_id, [3, 4])) {
            return;
        }

        // Common leaves
        $this->updateCommonLeaves($contract, $sick, $unpaid);

        // Gender and marital-specific leaves
        $this->updateSpecificLeaves($employee, $contract, $leaveTypes, $dailyWorkHours);
    }

    /** ------------------------------------------------------------
     *  HELPER METHODS
     * -------------------------------------------------------------*/
    private function getAnnualLeaveRule($employee): AnnualLeaveRule
    {
        return AnnualLeaveRule::where('years_of_volunteering', '<=', $employee->years_of_service)
            ->orderBy('days', 'desc')
            ->first()
            ?? AnnualLeaveRule::first();
    }

    private function calculateExtraLeave($employee, $contract): float
    {
        $extra = 0;

        if ($employee->allContracts->count() <= 3 && $employee->allContracts->count() > 1) {
            $prevContract = $employee->allContracts()
                ->whereNot('id', $contract->id)
                ->with(['leaveBalances' => fn ($q) => $q->where('type_id', LeaveTypeCases::Annual)])
                ->orderBy('end_date', 'desc')
                ->first();

            if (! $prevContract || ! in_array($prevContract->contract_type_id, [3, 4])) {
                return $extra;
            }

            if ($prevContract->end_date->year !== $contract->start_date->year) {
                return $extra;
            }

            $prevBalance = $prevContract->leaveBalances[0] ?? null;
            if (! $prevBalance) {
                return $extra;
            }

            if ($prevContract->start_date->year === $prevContract->end_date->year) {
                return $prevBalance->deserved - $prevBalance->used;
            }

            $startOfYear = Carbon::create($contract->start_date->year, 1, 1);
            $endOfPrev = $prevContract->end_date;

            if ($endOfPrev > $startOfYear) {
                $monthsInNewYear = round($startOfYear->diffInMonths($endOfPrev));
                $remain = $prevBalance->deserved - $prevBalance->used;
                $extra = $remain >= $monthsInNewYear ? $monthsInNewYear * $contract->work_hour_per_day : $remain;
            }
        }

        return $extra;
    }

    private function calculateDeservedLeaves($duration, $rule, $dailyWorkHours, $extra): array
    {
        if ($duration < 12) {
            $yearly = (round($duration * $rule->day_per_month) * $dailyWorkHours) + $extra;
            $sick = $unpaid = $duration * $dailyWorkHours;
        } else {
            $yearly = ($rule->days * $dailyWorkHours) + $extra;
            $sick = $unpaid = 12 * $dailyWorkHours;
        }

        return [$yearly, $sick, $unpaid];
    }

    private function createCommonLeaves($contract, $sick, $unpaid): void
    {
        foreach ([
            LeaveTypeCases::FullSick => $sick,
            LeaveTypeCases::HalfSick => $sick,
            LeaveTypeCases::Unpaid => $unpaid,
        ] as $type => $deserved) {
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => $type,
                'deserved' => $deserved,
            ]);
        }
    }

    private function updateCommonLeaves($contract, $sick, $unpaid): void
    {
        $updates = [
            LeaveTypeCases::FullSick => $sick,
            LeaveTypeCases::HalfSick => $sick,
            LeaveTypeCases::Unpaid => $unpaid,
        ];

        foreach ($updates as $type => $deserved) {
            $contract->leaveBalances->where('type_id', $type)->first()?->update(['deserved' => $deserved]);
        }
    }

    private function createSpecificLeaves($employee, $contract, $leaveTypes, $dailyWorkHours): void
    {
        $allowedForAll = $leaveTypes->filter(fn ($t) => $t->allowed_gender === LeaveTypeEnum::GENDER_ANY &&
            $t->marital_status === LeaveTypeEnum::MARITAL_STATUS_ANY,
        );

        foreach ($allowedForAll as $type) {
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => $type->id,
                'deserved' => $type->base_days * $dailyWorkHours,
            ]);
        }

        if ($employee->marital_status === MaritalStatus::Single) {
            $this->createSingleLeaves($employee, $contract, $leaveTypes, $dailyWorkHours);
        } elseif ($employee->marital_status === MaritalStatus::Married) {
            $this->createMarriedLeaves($employee, $contract, $leaveTypes, $dailyWorkHours);
        }
    }

    private function updateSpecificLeaves($employee, $contract, $leaveTypes, $dailyWorkHours): void
    {
        if ($employee->marital_status === MaritalStatus::Single) {
            $this->updateSingleLeaves($employee, $contract, $leaveTypes, $dailyWorkHours);
        } elseif ($employee->marital_status === MaritalStatus::Married) {
            $this->updateMarriedLeaves($employee, $contract, $leaveTypes, $dailyWorkHours);
        }
    }

    private function createSingleLeaves($employee, $contract, $leaveTypes, $dailyWorkHours): void
    {
        $types = $leaveTypes->filter(fn ($t) => $t->marital_status === LeaveTypeEnum::MARITAL_STATUS_SINGLE);

        foreach ($types as $type) {
            if ($employee->gender === $type->allowed_gender || $type->allowed_gender === LeaveTypeEnum::GENDER_ANY) {
                LeaveBalance::create([
                    'contract_id' => $contract->id,
                    'type_id' => $type->id,
                    'deserved' => $type->base_days * $dailyWorkHours,
                ]);
            }
        }
    }

    private function updateSingleLeaves($employee, $contract, $leaveTypes, $dailyWorkHours): void
    {
        $types = $leaveTypes->filter(fn ($t) => $t->marital_status === LeaveTypeEnum::MARITAL_STATUS_SINGLE);

        $contract->leaveBalances
            ->whereIn('type_id', [LeaveTypeCases::MaternityFirst, LeaveTypeCases::MaternitySecond, LeaveTypeCases::Paternity])
            ->each(fn ($balance) => $balance->delete());

        foreach ($types as $type) {
            if ($employee->gender === $type->allowed_gender || $type->allowed_gender === LeaveTypeEnum::GENDER_ANY) {
                $balance = $contract->leaveBalances->where('type_id', $type->id)->first();
                if ($balance) {
                    $balance->update(['deserved' => $type->base_days * $dailyWorkHours]);
                } else {
                    LeaveBalance::create([
                        'contract_id' => $contract->id,
                        'type_id' => $type->id,
                        'deserved' => $type->base_days * $dailyWorkHours,
                    ]);
                }
            }
        }
    }

    private function createMarriedLeaves($employee, $contract, $leaveTypes, $dailyWorkHours): void
    {
        $types = $leaveTypes->filter(fn ($t) => $t->marital_status === LeaveTypeEnum::MARITAL_STATUS_MARRIED &&
            $t->allowed_gender === $employee->gender,
        );
        foreach ($types as $type) {
            if ($employee->gender === Gender::Female && ! $employee->abilityToTakeLeave($type->id)) {
                LeaveBalance::create([
                    'contract_id' => $contract->id,
                    'type_id' => $type->id,
                    'deserved' => $type->base_days * $dailyWorkHours,
                ]);
            } elseif ($employee->gender === Gender::Male) {
                LeaveBalance::create([
                    'contract_id' => $contract->id,
                    'type_id' => $type->id,
                    'deserved' => $type->base_days * $dailyWorkHours,
                ]);
            }
        }
    }

    private function updateMarriedLeaves($employee, $contract, $leaveTypes, $dailyWorkHours): void
    {
        $contract->leaveBalances->where('type_id', LeaveTypeCases::Marriage)->first()?->delete();

        $types = $leaveTypes->filter(fn ($t) => $t->marital_status === LeaveTypeEnum::MARITAL_STATUS_MARRIED &&
            $t->allowed_gender === $employee->gender,
        );

        foreach ($types as $type) {
            $balance = $contract->leaveBalances->where('type_id', $type->id)->first();
            $deserved = $type->base_days * $dailyWorkHours;

            if ($employee->gender === Gender::Female && ! $employee->abilityToTakeLeave($type->id)) {
                $balance
                    ? $balance->update(['deserved' => $deserved])
                    : LeaveBalance::create(['contract_id' => $contract->id, 'type_id' => $type->id, 'deserved' => $deserved]);
            } elseif ($employee->gender === Gender::Male) {
                $balance
                    ? $balance->update(['deserved' => $deserved])
                    : LeaveBalance::create(['contract_id' => $contract->id, 'type_id' => $type->id, 'deserved' => $deserved]);
            }
        }
    }
}
