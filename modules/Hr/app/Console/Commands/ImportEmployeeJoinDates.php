<?php

namespace Modules\Hr\Console\Commands;

use Modules\Hr\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ImportEmployeeJoinDates extends Command
{
    protected $signature = 'employees:import-join-dates
        {path : Spreadsheet holding the employee id and the joining date (e.g. "Leaves for Syria.xlsx")}
        {--sheet=0 : Sheet index to read}
        {--id-column=ID : Header of the column holding the employee id}
        {--date-column=Start Date : Header of the column holding the joining date}
        {--dry-run : Show what would be written without touching the database}
        {--force : Overwrite employees that already have a joined_at}';

    protected $description = 'Fill employees.joined_at from a spreadsheet of employee id → joining date.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! File::isFile($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $rows = $this->readRows($path);
        } catch (Throwable $e) {
            $this->error('Could not read the spreadsheet: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($rows === null) {
            return self::FAILURE;
        }

        // The source sheet lists one row per contract/project, so the same
        // person can appear several times. Joining the organisation happened
        // once: keep the earliest date.
        $dates = [];
        $duplicates = [];
        $unreadable = [];

        foreach ($rows as [$rowNumber, $rawId, $rawDate]) {
            $id = $this->parseId($rawId);
            $date = $this->parseDate($rawDate);

            if (! $id || ! $date) {
                $unreadable[] = [$rowNumber, $this->stringify($rawId), $this->stringify($rawDate)];

                continue;
            }

            if (isset($dates[$id])) {
                $duplicates[$id] = true;
                $dates[$id] = $date->lt($dates[$id]) ? $date : $dates[$id];

                continue;
            }

            $dates[$id] = $date;
        }

        if ($dates === []) {
            $this->error('No usable id / date pairs found — check --id-column and --date-column.');

            return self::FAILURE;
        }

        ksort($dates);

        $employees = Employee::whereIn('id', array_keys($dates))->get()->keyBy('id');

        $updated = 0;
        $skipped = 0;
        $unchanged = 0;
        $missing = [];

        foreach ($dates as $id => $date) {
            $employee = $employees->get($id);

            if (! $employee) {
                $missing[] = [$id, $date->toDateString()];

                continue;
            }

            $current = $employee->joined_at;

            if ($current && $current->isSameDay($date)) {
                $unchanged++;

                continue;
            }

            if ($current && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            $this->line(sprintf(
                '%s #%d %s: %s -> %s',
                $this->option('dry-run') ? '·' : '✔',
                $id,
                $employee->name,
                $current?->toDateString() ?? '—',
                $date->toDateString(),
            ));

            if (! $this->option('dry-run')) {
                $employee->joined_at = $date;
                $employee->save();
            }

            $updated++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d employee(s) %s, %d already correct, %d left untouched (already set — use --force to overwrite).',
            $this->option('dry-run') ? 'Dry run —' : 'Done.',
            $updated,
            $this->option('dry-run') ? 'would be updated' : 'updated',
            $unchanged,
            $skipped,
        ));

        if ($duplicates) {
            $this->newLine();
            $this->warn(count($duplicates).' id(s) appeared more than once; the earliest date was used: '
                .implode(', ', array_keys($duplicates)));
        }

        if ($unreadable) {
            $this->newLine();
            $this->warn(count($unreadable).' row(s) had no usable id/date:');
            $this->table(['Row', 'Id cell', 'Date cell'], $unreadable);
        }

        if ($missing) {
            $this->newLine();
            $this->warn(count($missing).' id(s) have no matching employee in this database:');
            $this->table(['Id', 'Date in file'], $missing);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0: int, 1: mixed, 2: mixed}>|null [row number, id cell, date cell]
     */
    private function readRows(string $path): ?array
    {
        $reader = IOFactory::createReaderForFile($path);

        // Data only: the source workbook drives its helper columns with
        // structured references to tables PhpSpreadsheet cannot resolve, and
        // recalculating them aborts the whole read.
        $reader->setReadDataOnly(true);

        $sheet = $reader->load($path)->getSheet((int) $this->option('sheet'));
        $grid = $sheet->toArray(null, false, false, false);

        if ($grid === []) {
            $this->error('The sheet is empty.');

            return null;
        }

        $header = array_map(
            fn ($cell) => mb_strtolower(trim((string) $cell)),
            array_shift($grid),
        );

        $idIndex = array_search(mb_strtolower(trim($this->option('id-column'))), $header, true);
        $dateIndex = array_search(mb_strtolower(trim($this->option('date-column'))), $header, true);

        if ($idIndex === false || $dateIndex === false) {
            $this->error(sprintf(
                'Could not find the columns "%s" and "%s". Header row is: %s',
                $this->option('id-column'),
                $this->option('date-column'),
                implode(' | ', $header),
            ));

            return null;
        }

        $rows = [];

        foreach ($grid as $offset => $row) {
            $id = $row[$idIndex] ?? null;
            $date = $row[$dateIndex] ?? null;

            // Skip fully blank rows rather than reporting them as unreadable.
            if (($id === null || $id === '') && ($date === null || $date === '')) {
                continue;
            }

            $rows[] = [$offset + 2, $id, $date];
        }

        return $rows;
    }

    private function parseId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel stores dates as a serial number of days since 1900/1904.
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function stringify(mixed $value): string
    {
        return $value === null ? '—' : (string) $value;
    }
}
