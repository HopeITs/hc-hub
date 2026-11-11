<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;

/**
 * The amendment sheet (ملحق عقد) to print and sign, for the browser's
 * print-to-PDF. The route checks ContractAmendmentPolicy::exportAmendment
 * (the contract print's rule: pay access).
 */
class ContractAmendmentPrintController extends Controller
{
    public function __invoke(Contract $contract, ContractAmendment $amendment): View
    {
        $contract->load('employee:id,first_name_en,father_name_en,last_name_en,first_name_ar,father_name_ar,last_name_ar,gender');
        $amendment->load('payTiers')->setRelation('contract', $contract);

        $after = $amendment->termsAsContract();

        // The sheet states every term that changed, whatever the contract's
        // print toggles say; toggles already on stay on, nothing that did not
        // change is switched on.
        $changed = $amendment->changedFields();
        $after->show_hours_per_week = $after->show_hours_per_week || in_array('weekly_hours', $changed, true);
        $after->show_hours_per_day = $after->show_hours_per_day
            || array_intersect(['work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to'], $changed) !== [];
        $dayRangeChanged = array_intersect(['work_day_from', 'work_day_to'], $changed) !== [];
        $after->show_day_range = $after->show_day_range || $dayRangeChanged;
        $after->show_days_per_week = $after->show_days_per_week || $dayRangeChanged;

        return view('hr::print.contract-legal.amendment', [
            'amendment' => $amendment,
            'contract' => $contract,
            'after' => $after,
            'fileTitle' => sprintf('%s-amendment-%d',
                Str::slug(trim($contract->employee?->first_name_en.' '.$contract->employee?->last_name_en)) ?: 'contract',
                $amendment->number,
            ),
        ]);
    }
}
