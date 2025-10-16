<?php

namespace App\Livewire\Forms;

use App\Enums\ContractType;
use App\Enums\Gender;
use App\Enums\LeaveTypeCases;
use App\Enums\MaritalStatus;
use App\Enums\SalaryPer;
use App\Models\AnnualLeaveRule;
use App\Models\Contract;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Enums\LeaveType as LeaveTypeEnum;
use Livewire\Form;

class LeaveBalanceForm extends Form
{
    public ?LeaveBalance $balance = null;

    public $contract_id;


    protected function rules()
    {
        return [
            'contract_id' => 'required|exists:contracts,id',
        ];
    }

    public function setLeaveBalance(LeaveBalance $balance): void
    {
        $this->balance = $balance;


    }

    public function store($contractId): void
    {
        $contract = Contract::findOrFail($contractId)->load(['employee', 'contractType']);
        $leaveTypes = LeaveType::whereNotIn('id', [1, 2])->get();
        $employee = $contract->employee;
        $leaveRule = AnnualLeaveRule::where('years_of_volunteering', '<=', $employee->years_of_service)->orderBy('days', 'desc')->first();
        $duration = $contract->duration();
        $fullTimeHours = 40;

        //handle if contract less than a year to set deserved of yearly leaves
        if ($duration < 12) {
            $deserved = $duration * $leaveRule->day_per_month;
        } else {
            $deserved = $leaveRule->days;
        }

        //case: full time or freelance
        if (($contract->contractType->type === ContractType::FullTime || $contract->contractType->type === ContractType::Freelance)) {

            //yearly leaves:
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => LeaveTypeCases::Annual,//yearly
                'deserved' => $deserved
            ]);

            //while this employee isn't in trial period
            if ($contract->contract_type_id !== 3 && $contract->contract_type_id !== 4) {

                //full sick:
                LeaveBalance::create([
                    'contract_id' => $contract->id,
                    'type_id' => LeaveTypeCases::FullSick,//yearly
                    'deserved' => min($duration, 12)
                ]);

                //leaves allowed for all genders and marital status
                $allowedForAll = $leaveTypes->filter(fn($type) => $type->allowed_gender === LeaveTypeEnum::GENDER_ANY && $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_ANY);
                foreach ($allowedForAll as $allowed) {
                    LeaveBalance::create([
                        'contract_id' => $contract->id,
                        'type_id' => $allowed->id,
                        'deserved' => $allowed->base_days
                    ]);
                }

                //leaves for singles
                if ($employee->marital_status === MaritalStatus::Single) {
                    $allowedForSingle = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_SINGLE);
                    foreach ($allowedForSingle as $allowed) {

                        //while gender match with allowed gender
                        if ($employee->gender === $allowed->allowed_gender || $allowed->allowed_gender === LeaveTypeEnum::GENDER_ANY) {
                            LeaveBalance::create([
                                'contract_id' => $contract->id,
                                'type_id' => $allowed->id,
                                'deserved' => $allowed->base_days
                            ]);
                        }
                    }
                } else {
                    //leaves for married
                    if ($employee->marital_status === MaritalStatus::Married) {

                        $allowedForMarried = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_MARRIED && $type->allowed_gender === $employee->gender);

                        if ($employee->gender === Gender::Female) {
                            if ($employee->abilityToTakeLeave($allowed->id)) {
                                LeaveBalance::create([
                                    'contract_id' => $contract->id,
                                    'type_id' => $allowed->id,
                                    'deserved' => $allowed->base_days
                                ]);
                            }
                        } else {
                            LeaveBalance::create([
                                'contract_id' => $contract->id,
                                'type_id' => $allowed->id,
                                'deserved' => $allowed->base_days
                            ]);
                        }


                    }
                }

            }


        } else {
            $workHoursPercentage = $fullTimeHours / $contract->weekly_hours;
            if ($workHoursPercentage > ($fullTimeHours / 2)) {
                $leavePercentage = 0.66; //3\2
            } else {
                $leavePercentage = 0.5; // 1\2;
            }
            //yearly leaves:
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => LeaveTypeCases::Annual,//yearly
                'deserved' => round($deserved * $leavePercentage)
            ]);

            //while this employee isn't in trial period
            if ($contract->contract_type_id !== 3 && $contract->contract_type_id !== 4) {

                //full sick:
                LeaveBalance::create([
                    'contract_id' => $contract->id,
                    'type_id' => LeaveTypeCases::FullSick,//yearly
                    'deserved' => round(min($duration, 12) * $leavePercentage)
                ]);

                //leaves allowed for all genders and marital status
                $allowedForAll = $leaveTypes->filter(fn($type) => $type->allowed_gender === LeaveTypeEnum::GENDER_ANY && $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_ANY);
                foreach ($allowedForAll as $allowed) {
                    LeaveBalance::create([
                        'contract_id' => $contract->id,
                        'type_id' => $allowed->id,
                        'deserved' => $deserved > 10 ? round($deserved * $leavePercentage) : $deserved
                    ]);
                }

                //leaves for singles
                if ($employee->marital_status === MaritalStatus::Single) {
                    $allowedForSingle = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_SINGLE);
                    foreach ($allowedForSingle as $allowed) {

                        //while gender match with allowed gender
                        if ($employee->gender === $allowed->allowed_gender || $allowed->allowed_gender === LeaveTypeEnum::GENDER_ANY) {
                            LeaveBalance::create([
                                'contract_id' => $contract->id,
                                'type_id' => $allowed->id,
                                'deserved' => round($deserved * $leavePercentage)
                            ]);
                        }
                    }
                } else {
                    //leaves for married
                    if ($employee->marital_status === MaritalStatus::Married) {
                        //handle mother leave taken once


                        $allowedForMarried = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_MARRIED);
                        foreach ($allowedForMarried as $allowed) {

                            //while gender match with allowed gender
                            if ($employee->gender === $allowed->allowed_gender || $allowed->allowed_gender === LeaveTypeEnum::GENDER_ANY) {
                                LeaveBalance::create([
                                    'contract_id' => $contract->id,
                                    'type_id' => $allowed->id,
                                    'deserved' => $deserved > 10 ? round($deserved * $leavePercentage) : $deserved
                                ]);
                            }
                        }
                    }
                }

            }


        }


    }

    public function update()
    {
        $this->validate();

        $this->balance->update(
            $this->all()
        );

        $this->reset();
    }


}
