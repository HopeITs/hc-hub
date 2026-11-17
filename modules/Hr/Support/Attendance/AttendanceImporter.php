<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceDeviceLink;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Employee;

/**
 * Turns a device report into stored punches. A punch is kept once however often it is uploaded
 * (device number + minute is unique), and a device number is an employee when HR linked it
 * (AttendanceDeviceLink) or when it is the employee's ID. Punches of a locked month are not taken
 * in, and new punches in an approved month take the approval back.
 */
class AttendanceImporter
{
    public function __construct(
        private readonly DeviceReportReader $reader,
        private readonly MonthWorkflow $workflow,
    ) {}

    /**
     * What an upload holds, before anything is stored.
     *
     * @return array{total: int, invalid: int, from: ?string, to: ?string, numbers: list<array{device_no: string, punches: int, employee: ?Employee}>}
     */
    public function preview(string $path): array
    {
        $report = $this->reader->read($path);
        $perNumber = [];
        $times = [];

        foreach ($report['punches'] as $punch) {
            $perNumber[$punch['device_no']] = ($perNumber[$punch['device_no']] ?? 0) + 1;
            $times[] = $punch['at'];
        }

        $numbers = array_map('strval', array_keys($perNumber));
        $employees = $this->employeesFor($numbers);

        $rows = array_map(fn (string $no): array => [
            'device_no' => $no,
            'punches' => (int) $perNumber[$no],
            'employee' => $employees[$no] ?? null,
        ], $numbers);

        usort($rows, fn (array $a, array $b): int => [$a['employee'] === null ? 0 : 1, (int) $a['device_no']] <=> [$b['employee'] === null ? 0 : 1, (int) $b['device_no']]);

        return [
            'total' => count($report['punches']),
            'invalid' => $report['invalid'],
            'from' => $times === [] ? null : min($times)->toDateString(),
            'to' => $times === [] ? null : max($times)->toDateString(),
            'numbers' => $rows,
        ];
    }

    public function import(string $path, string $fileName, ?User $by): AttendanceImport
    {
        $punches = $this->reader->read($path)['punches'];

        return DB::transaction(function () use ($punches, $fileName, $by): AttendanceImport {
            $numbers = array_values(array_unique(array_column($punches, 'device_no')));
            $employees = $this->employeesFor($numbers);
            $times = array_column($punches, 'at');

            $import = AttendanceImport::query()->create([
                'user_id' => $by?->id,
                'file_name' => mb_substr($fileName, 0, 255),
                'period_start' => $times === [] ? null : min($times)->toDateString(),
                'period_end' => $times === [] ? null : max($times)->toDateString(),
                'punches_total' => count($punches),
                'device_numbers' => count($numbers),
            ]);

            // A locked month does not change: its punches are not taken in.
            $locked = AttendanceMonth::query()->whereNotNull('locked_at')->get(['employee_id', 'month'])
                ->mapWithKeys(fn (AttendanceMonth $month): array => [$month->employee_id.'|'.$month->month->format('Y-m') => true]);

            $now = now();
            $rows = [];
            $skipped = 0;

            foreach ($punches as $punch) {
                $employeeId = ($employees[$punch['device_no']] ?? null)?->id;

                if ($employeeId !== null && isset($locked[$employeeId.'|'.$punch['at']->format('Y-m')])) {
                    $skipped++;

                    continue;
                }

                $rows[] = [
                    'import_id' => $import->id,
                    'device_no' => $punch['device_no'],
                    'employee_id' => $employeeId,
                    'punched_at' => $punch['at']->format('Y-m-d H:i:s'),
                    'created_at' => $now,
                ];
            }

            $new = 0;

            foreach (array_chunk($rows, 500) as $chunk) {
                $new += AttendancePunch::query()->insertOrIgnore($chunk);
            }

            $import->update(['punches_new' => $new, 'punches_locked' => $skipped]);

            // New punches in an approved month: what was approved is no longer what the month holds.
            $this->withdrawApprovals(AttendancePunch::query()->where('import_id', $import->id)->whereNotNull('employee_id')->get(['employee_id', 'punched_at']));

            return $import;
        });
    }

    /** Tie a device number to an employee and give the punches already stored for it their owner. */
    public function link(string $deviceNo, Employee $employee, ?User $by): void
    {
        DB::transaction(function () use ($deviceNo, $employee, $by): void {
            AttendanceDeviceLink::query()->updateOrCreate(
                ['device_no' => $deviceNo],
                ['employee_id' => $employee->id, 'created_by' => $by?->id],
            );

            AttendancePunch::query()->where('device_no', $deviceNo)->update(['employee_id' => $employee->id]);

            $this->withdrawApprovals(AttendancePunch::query()->where('device_no', $deviceNo)->get(['employee_id', 'punched_at']));
        });
    }

    /**
     * Who each device number is: a hand-made link first, then the employee whose ID it is.
     *
     * @param  list<string>  $numbers
     * @return array<string, Employee>
     */
    public function employeesFor(array $numbers): array
    {
        $found = [];

        foreach (AttendanceDeviceLink::query()->with('employee')->whereIn('device_no', $numbers)->get() as $link) {
            if ($link->employee !== null) {
                $found[$link->device_no] = $link->employee;
            }
        }

        if (config('attendance.device_number_is_employee_id')) {
            $ids = array_values(array_map('intval', array_filter($numbers, fn (string $no): bool => ! isset($found[$no]) && ctype_digit($no))));

            foreach (Employee::query()->whereIn('id', $ids)->get() as $employee) {
                $found[(string) $employee->id] ??= $employee;
            }
        }

        return $found;
    }

    /** @param  Collection<int, AttendancePunch>  $punches */
    private function withdrawApprovals(Collection $punches): void
    {
        $punches
            ->filter(fn (AttendancePunch $punch): bool => $punch->employee_id !== null)
            ->map(fn (AttendancePunch $punch): string => $punch->employee_id.'|'.$punch->punched_at->format('Y-m'))
            ->unique()
            ->each(function (string $key): void {
                [$employeeId, $month] = explode('|', $key);
                $this->workflow->withdrawApproval((int) $employeeId, CarbonImmutable::createFromFormat('!Y-m', $month) ?: CarbonImmutable::now());
            });
    }
}
