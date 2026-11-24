<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Reports;

use App\Support\CsvExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Any report as one CSV file a spreadsheet opens cleanly: each block (sheet)
 * is a title line, its headings and its rows, blocks separated by an empty
 * line. The hub has no workbook library, so this stands in for the HR app's
 * multi-sheet Excel files.
 */
final class ReportCsv
{
    /**
     * @param  list<array{title: string, headings: list<string>, rows: list<array<int, mixed>>}>  $sheets
     */
    public static function download(string $filename, array $sheets): StreamedResponse
    {
        $lines = [];

        foreach ($sheets as $index => $sheet) {
            if ($index > 0) {
                $lines[] = [];
            }

            $lines[] = [$sheet['title']];
            $lines[] = $sheet['headings'];

            foreach ($sheet['rows'] as $row) {
                $lines[] = $row;
            }
        }

        return CsvExport::download($filename, [__('hr_reports.csv.report')], $lines);
    }
}
