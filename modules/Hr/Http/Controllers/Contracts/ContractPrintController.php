<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Hr\Models\Contract;

class ContractPrintController extends Controller
{
    /**
     * A plain bilingual page of the contract's terms for the browser's
     * print-to-PDF; the old app rendered a PDF with Browsershot, which the
     * hub does not ship. The route checks ContractPolicy::export, which
     * requires pay access, so the pay clause is always shown here.
     */
    public function __invoke(Contract $contract): View
    {
        $contract->load([
            'employee:id,first_name_en,father_name_en,last_name_en,first_name_ar,father_name_ar,last_name_ar,national_number,city_id,username',
            'position:id,title_en,title_ar,program_id,center_id,department_id',
            'payTiers' => fn ($tiers) => $tiers->select(['id', 'contract_id', 'condition_en', 'condition_ar', 'amount', 'sort_order'])->orderBy('sort_order'),
        ]);

        return view('hr::pages.contracts._print', ['contract' => $contract]);
    }
}
