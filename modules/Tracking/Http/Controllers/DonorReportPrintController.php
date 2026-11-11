<?php

declare(strict_types=1);

namespace Modules\Tracking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Tracking\Models\DonorReport;

class DonorReportPrintController extends Controller
{
    /**
     * A plain page of the saved snapshot for the browser's print-to-PDF (no
     * live re-query). The route checks DonorReportPolicy::view; the finance
     * block renders only for funding-agreements.view holders.
     */
    public function __invoke(Request $request, DonorReport $donorReport): View
    {
        abort_unless($donorReport->isComposed() && is_array($donorReport->snapshot), 404);

        return view('tracking::pages.donor-reports._print', [
            'report' => $donorReport,
            'showFinance' => $request->user()?->can('funding-agreements.view') === true,
        ]);
    }
}
