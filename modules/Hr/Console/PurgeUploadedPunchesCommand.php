<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\AttendancePunch;

/**
 * Removes every punch that came from an uploaded report file (no device behind it) and the records of
 * those uploads, so that what a device or the LAN agent sends afterwards is stored under that device. A
 * punch is kept once per device number + minute, so while the file rows stay, the device's copy of the
 * same punch would be ignored and keep looking like an upload.
 *
 * PERMANENT. Locked months are included and the device will not refill them (a locked month takes no
 * new punches), so run --dry-run first. Approved months lose their approval, since their punches change.
 */
class PurgeUploadedPunchesCommand extends Command
{
    protected $signature = 'attendance:purge-uploaded {--dry-run : Only count, delete nothing} {--force : Do not ask}';

    protected $description = 'Delete every punch that came from an uploaded report file (permanent)';

    public function handle(): int
    {
        $punches = AttendancePunch::query()->whereNull('device_id');
        $imports = AttendanceImport::query()->where('source', 'file');

        $uploaded = AttendancePunch::query()->whereNull('device_id')
            ->selectRaw('count(*) as total, min(punched_at) as first_at, max(punched_at) as last_at')->first();

        $locked = AttendanceMonth::query()->whereNotNull('locked_at')->count();
        $approved = AttendanceMonth::query()->whereNotNull('approved_at')->whereNull('locked_at')->count();

        $this->line(sprintf('Uploaded punches: %d (%s -> %s)', $uploaded?->getAttribute('total') ?? 0, $uploaded?->getAttribute('first_at') ?? '-', $uploaded?->getAttribute('last_at') ?? '-'));
        $this->line(sprintf('Upload records:   %d', $imports->count()));
        $this->line(sprintf('Punches kept (from devices): %d', AttendancePunch::query()->whereNotNull('device_id')->count()));
        $this->line(sprintf('Months locked: %d (the device will NOT refill them) · approved, not locked: %d (lose approval)', $locked, $approved));

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete these permanently?')) {
            $this->warn('Cancelled.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($punches, $imports): void {
            $affected = $punches->clone()->whereNotNull('employee_id')->distinct()->pluck('employee_id');

            $punches->delete();
            $imports->delete();

            // Their approval rested on punches that are gone.
            AttendanceMonth::query()->whereIn('employee_id', $affected)->whereNotNull('approved_at')->whereNull('locked_at')
                ->update(['approved_at' => null, 'approved_by' => null]);
        });

        $this->info('Deleted. Run the agent (node agent.cjs --full) or let the device push to load its history.');

        return self::SUCCESS;
    }
}
