<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Support\Attendance\Roster;
use Modules\Hr\Support\Employees\HrMedia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The signed paper of a person's month, for anyone who may see that person's timesheet (Roster).
 * It lives on the private side of the HR disk and is never reachable by URL guessing.
 */
class AttendanceSignedController extends Controller
{
    public const COLLECTION = 'signed';

    /** What a signed copy may be: a scan or a photo of the sheet. */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public function __invoke(Request $request, AttendanceMonth $month, Roster $roster): Response
    {
        $user = $request->user();
        $employee = $month->employee()->first();

        abort_unless($user instanceof User && $employee !== null, 404);
        abort_unless($roster->covers($user, $employee, $month->month), 403);

        $file = HrMedia::for(HrMedia::ATTENDANCE_MONTH, (int) $month->id, self::COLLECTION)->last();

        abort_if($file === null || ! in_array(HrMedia::extension($file), self::ALLOWED_EXTENSIONS, true), 404);

        return HrMedia::response($file, inline: true);
    }
}
