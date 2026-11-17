<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Receives the attendance report as an ordinary form post. Livewire's own
 * uploads accept only the hub's document types (FileType), and the device
 * program saves .xls or CSV, so the file is kept here, in the uploader's own
 * private folder, and the upload page reads it from there (preview first,
 * nothing stored until HR confirms).
 */
class AttendanceUploadController extends Controller
{
    public const DISK = 'local';

    public const EXTENSIONS = ['xls', 'csv', 'txt'];

    public static function folder(int $userId): string
    {
        return 'attendance-uploads/'.$userId;
    }

    /** The stored report of this user by its token, or null when it is not theirs or gone. */
    public static function path(int $userId, string $token): ?string
    {
        if (! preg_match('/^[0-9a-f-]{36}\.('.implode('|', self::EXTENSIONS).')$/', $token)) {
            return null;
        }

        $relative = self::folder($userId).'/'.$token;

        return Storage::disk(self::DISK)->exists($relative) ? Storage::disk(self::DISK)->path($relative) : null;
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'report' => ['required', 'file', 'max:20480', 'extensions:'.implode(',', self::EXTENSIONS)],
        ]);

        $file = $request->file('report');
        $token = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());

        // One report waits at a time: an earlier one not imported is dropped.
        Storage::disk(self::DISK)->deleteDirectory(self::folder((int) $request->user()->id));
        $file->storeAs(self::folder((int) $request->user()->id), $token, self::DISK);

        $request->session()->put('attendance_upload_name', mb_substr($file->getClientOriginalName(), 0, 255));

        return redirect()->route('hr.attendance.upload', ['report' => $token]);
    }
}
