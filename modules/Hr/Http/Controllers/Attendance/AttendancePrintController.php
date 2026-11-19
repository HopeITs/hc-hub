<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Hr\Support\Attendance\PrintSelection;

/**
 * The printed Monthly TimeSheet (page 1) and Monthly Summary (page 2) of everyone the user may see
 * for a month, one pair of pages per person, printed from the browser (A4, Arabic, RTL). A sheet
 * with days still waiting for an answer is marked DRAFT on every page.
 */
class AttendancePrintController extends Controller
{
    public function __invoke(Request $request, PrintSelection $selection): View
    {
        return view('hr::print.attendance.timesheets', $selection->fromRequest($request));
    }
}
