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

    public $used = 0;


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
        $leaveTypes = LeaveType::whereNot('base_days', 12)->get();
        $employee = $contract->employee;
        $leaveRule = AnnualLeaveRule::where('years_of_volunteering', '<=', $employee->years_of_service)->orderBy('days', 'desc')->first() ?? AnnualLeaveRule::first();
        $duration = $contract->duration();
        $weekWordDays = 5;
        $DailyWorkHours = $contract->weekly_hours / $weekWordDays;


        //handle if contract less than a year to set deserved of yearly leaves
        if ($duration < 12) {
            $yearly_deserve = round($duration * $leaveRule->day_per_month) * $DailyWorkHours;
            $sick_deserve = $duration * $DailyWorkHours;
            $unpaid_deserve = $duration * $DailyWorkHours;
        } else {
            $yearly_deserve = $leaveRule->days * $DailyWorkHours;
            $sick_deserve = 12 * $DailyWorkHours;
            $unpaid_deserve = 12 * $DailyWorkHours;
        }


        //yearly leaves:
        LeaveBalance::create([
            'contract_id' => $contract->id,
            'type_id' => LeaveTypeCases::Annual,//yearly
            'deserved' => $yearly_deserve
        ]);

        //while this employee isn't in trial period
        if ($contract->contract_type_id !== 3 && $contract->contract_type_id !== 4) {
            //full sick:
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => LeaveTypeCases::FullSick,
                'deserved' => $sick_deserve
            ]);

            //half sick:
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => LeaveTypeCases::HalfSick,
                'deserved' => $sick_deserve
            ]);

            //unpaid
            LeaveBalance::create([
                'contract_id' => $contract->id,
                'type_id' => LeaveTypeCases::Unpaid,
                'deserved' => $unpaid_deserve
            ]);

            //leaves allowed for all genders and marital status
            $allowedForAll = $leaveTypes->filter(fn($type) => $type->allowed_gender === LeaveTypeEnum::GENDER_ANY && $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_ANY);
            foreach ($allowedForAll as $allowed) {
                LeaveBalance::create([
                    'contract_id' => $contract->id,
                    'type_id' => $allowed->id,
                    'deserved' => $allowed->base_days * $DailyWorkHours
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
                            'deserved' => $allowed->base_days * $DailyWorkHours
                        ]);
                    }
                }
            } else {
                //leaves for married

                if ($employee->marital_status === MaritalStatus::Married) {

                    $allowedForMarried = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_MARRIED && $type->allowed_gender === $employee->gender);

                    if ($employee->gender === Gender::Female) {
                        foreach ($allowedForMarried as $allowed) {
                            if (!$employee->abilityToTakeLeave($allowed->id)) {
                                LeaveBalance::create([
                                    'contract_id' => $contract->id,
                                    'type_id' => $allowed->id,
                                    'deserved' => $allowed->base_days * $DailyWorkHours
                                ]);
                            }
                        }
                    } else {
                        foreach ($allowedForMarried as $allowed) {
                            LeaveBalance::create([
                                'contract_id' => $contract->id,
                                'type_id' => $allowed->id,
                                'deserved' => $allowed->base_days * $DailyWorkHours
                            ]);
                        }
                    }


                }
            }

        }


    }

    public function update($contractId): void
    {
        $contract = Contract::findOrFail($contractId)->load(['employee', 'contractType' , 'leaveBalances']);
        $balances = $contract->leaveBalances;
        $leaveTypes = LeaveType::whereNot('base_days', 12)->get();
        $employee = $contract->employee;
        $leaveRule = AnnualLeaveRule::where('years_of_volunteering', '<=', $employee->years_of_service)->orderBy('days', 'desc')->first() ?? AnnualLeaveRule::first();
        $duration = $contract->duration();
        $weekWordDays = 5;
        $DailyWorkHours = $contract->weekly_hours / $weekWordDays;


        //handle if contract less than a year to set deserved of yearly leaves
        if ($duration < 12) {
            $yearly_deserve = round($duration * $leaveRule->day_per_month) * $DailyWorkHours;
            $sick_deserve = $duration * $DailyWorkHours;
            $unpaid_deserve = $duration * $DailyWorkHours;
        } else {
            $yearly_deserve = $leaveRule->days * $DailyWorkHours;
            $sick_deserve = 12 * $DailyWorkHours;
            $unpaid_deserve = 12 * $DailyWorkHours;
        }
        $contract->leaveBalances->where('type_id', LeaveTypeCases::Annual)->first()->update([
            'deserved' => $yearly_deserve
        ]);



        //while this employee isn't in trial period
        if ($contract->contract_type_id !== 3 && $contract->contract_type_id !== 4) {
            //full sick:
            $contract->leaveBalances->where('type_id', LeaveTypeCases::FullSick)->first()->update([
                'deserved' => $sick_deserve
            ]);

            //half sick:
            $contract->leaveBalances->where('type_id', LeaveTypeCases::HalfSick)->first()->update([
                'deserved' => $sick_deserve
            ]);

            //unpaid
            $contract->leaveBalances->where('type_id', LeaveTypeCases::Unpaid)->first()->update([
                'deserved' => $unpaid_deserve
            ]);


            //leaves for singles
            if ($employee->marital_status === MaritalStatus::Single) {
                $allowedForSingle = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_SINGLE);

                //delete any previous marriage if exist
                $contract->leaveBalances->whereIn('type_id',[LeaveTypeCases::MaternityFirst,LeaveTypeCases::MaternitySecond,LeaveTypeCases::Paternity])
                    ->each(function ($leaveBalances)  {
                        $leaveBalances->delete();
                    });
                foreach ($allowedForSingle as $allowed) {

                    //while gender match with allowed gender
                    if ($employee->gender === $allowed->allowed_gender || $allowed->allowed_gender === LeaveTypeEnum::GENDER_ANY) {
                      $balance =   $contract->leaveBalances->where('type_id', $allowed->id)->first();
                          if(isset($balance)){
                              $balance   ->update([
                                  'deserved' => $allowed->base_days * $DailyWorkHours
                              ]);
                          }else{
                              LeaveBalance::create([
                                  'contract_id' => $contract->id,
                                  'type_id' => $allowed->id,
                                  'deserved' => $allowed->base_days * $DailyWorkHours
                              ]);
                          }

                    }
                }
            } else {
                //leaves for married
                if ($employee->marital_status === MaritalStatus::Married) {

                    //delete marriage leave if exist
                    $contract->leaveBalances->where('type_id',LeaveTypeCases::Marriage)->first()?->delete();
                    $allowedForMarried = $leaveTypes->filter(fn($type) => $type->marital_status === LeaveTypeEnum::MARITAL_STATUS_MARRIED && $type->allowed_gender === $employee->gender);
                    if ($employee->gender === Gender::Female) {
                        foreach ($allowedForMarried as $allowed) {
                            if (!$employee->abilityToTakeLeave($allowed->id)) {
                                $balance = $contract->leaveBalances->where('type_id', $allowed->id)->first();
                                    if(isset($balance)){
                                        $balance->update([
                                            'deserved' => $allowed->base_days * $DailyWorkHours
                                        ]);
                                    }else{
                                        //add the leave
                                        LeaveBalance::create([
                                            'contract_id' => $contract->id,
                                            'type_id' => $allowed->id,
                                            'deserved' => $allowed->base_days * $DailyWorkHours
                                        ]);

                                    }


                            }
                        }
                    } else {
                        foreach ($allowedForMarried as $allowed) {
                            $contract->leaveBalances->where('type_id', $allowed->id)->first()->update([
                                'deserved' => $allowed->base_days * $DailyWorkHours
                            ]);
                        }
                    }


                }
            }

        }


    }


}
