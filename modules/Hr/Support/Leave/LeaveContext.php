<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Leave;

use Illuminate\Database\Eloquent\Collection;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;

/**
 * The relations the leave rules read, loaded in one go so strict mode never
 * meets a lazy load: pricing reads contract.position.center and
 * contract.employee, eligibility the balances' types, the decision authority
 * the position's direct manager.
 */
final class LeaveContext
{
    /** @var list<string> */
    public const CONTRACT_RELATIONS = [
        'employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,gender,marital_status,city_id,is_active,dob,actual_dob',
        'position:id,center_id,city_id,program_id,department_id,job_level_id,direct_manager_id,tech_manager_id',
        'position.center:id,city_id,weekends,open_at,close_at',
    ];

    /** @var list<string> */
    public const REQUEST_RELATIONS = [
        'leaveBalance:id,contract_id,type_id,deserved,used,amendment_offset',
        'leaveBalance.leaveType:id,name_en,name_ar,base_days',
        'leaveBalance.contract:id,employee_id,position_id,contract_type_id,type,weekly_hours,schedule_mode,start_date,end_date',
        'leaveBalance.contract.employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,gender,marital_status,city_id,is_active,dob,actual_dob',
        'leaveBalance.contract.position:id,center_id,city_id,program_id,department_id,job_level_id,direct_manager_id,tech_manager_id',
        'leaveBalance.contract.position.center:id,city_id,weekends,open_at,close_at',
    ];

    /**
     * The employee's current contract (the newest running one, as
     * Employee::latestActiveContract()) with what the rules read, its balances
     * included and pointing back at it.
     */
    public static function contractOf(int $employeeId): ?Contract
    {
        $contract = Contract::query()
            ->activeNow()
            ->where('employee_id', $employeeId)
            ->with([...self::CONTRACT_RELATIONS, 'leaveBalances.leaveType:id,name_en,name_ar,base_days'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($contract !== null) {
            $contract->leaveBalances->each(fn (LeaveBalance $balance) => $balance->setRelation('contract', $contract));
        }

        return $contract;
    }

    /** One request with everything its rules and its page read. */
    public static function request(int $leaveRequestId, bool $lock = false): LeaveRequest
    {
        return LeaveRequest::query()
            ->with(self::REQUEST_RELATIONS)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($leaveRequestId);
    }

    /**
     * @param  Collection<int, LeaveRequest>  $requests
     */
    public static function load(Collection $requests): void
    {
        $requests->loadMissing(self::REQUEST_RELATIONS);
    }
}
