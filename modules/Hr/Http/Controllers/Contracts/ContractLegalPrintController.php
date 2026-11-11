<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Support\Contracts\ContractOptions;

/**
 * The full legal text of a contract — the annual, trial or service template,
 * page by page on the letterhead — for the browser's print-to-PDF. The old
 * app rendered it with Browsershot, which the hub does not ship. The route
 * checks ContractPolicy::export (pay access), as the stipend clause is in it.
 */
class ContractLegalPrintController extends Controller
{
    /** Pages in the printed layout, per contract category. */
    private const PAGES_PER_CATEGORY = ['annual' => 5, 'trial' => 4, 'service' => 4];

    public function __invoke(Contract $contract): View
    {
        $contract->load([
            'employee:id,first_name_en,father_name_en,last_name_en,first_name_ar,father_name_ar,last_name_ar,national_number,dob,pob_en,pob_ar,nationality,address_en,address_ar,gender',
            'position:id,title_en,title_ar,program_id,center_id,city_id',
            'position.program:id,name',
            'position.center:id,name_en,name_ar',
            'position.city:id,name_en,name_ar',
            'contractType:id,name_en,name_ar',
            'payTiers' => fn ($tiers) => $tiers->orderBy('sort_order'),
            'appliedAmendments.payTiers',
        ]);

        $category = ContractOptions::category($contract->contract_type_id);
        $signed = $contract->asSigned();

        return view('hr::print.contract-legal.document', [
            // The signed contract, not its current terms: amendments are their own sheets.
            'contract' => $signed,
            'category' => $category,
            'totalPages' => self::PAGES_PER_CATEGORY[$category] ?? 5,
            'leaveDays' => $this->annualLeaveDays($contract, (int) $signed->weekly_hours),
            'hasAmendments' => $contract->appliedAmendments->isNotEmpty(),
            'amendmentsUrl' => Gate::allows('view', $contract) ? route('hr.contracts.amendments.index', $contract->id) : null,
            'fileTitle' => sprintf('%s-%s-%s',
                Str::slug(trim($contract->employee?->first_name_en.' '.$contract->employee?->last_name_en)) ?: 'contract',
                $category,
                now()->format('Ymd'),
            ),
        ]);
    }

    /**
     * The annual leave the contract grants, in whole working days: the
     * balance is kept in hours, a working day is a fifth of the weekly hours.
     */
    private function annualLeaveDays(Contract $contract, int $weeklyHours): ?int
    {
        $hoursPerDay = intdiv($weeklyHours, 5);

        if ($hoursPerDay < 1) {
            return null;
        }

        $deserved = LeaveBalance::query()
            ->where('contract_id', $contract->id)
            ->where('type_id', LeaveTypeCases::Annual)
            ->value('deserved');

        return $deserved === null ? null : intdiv((int) $deserved, $hoursPerDay);
    }
}
