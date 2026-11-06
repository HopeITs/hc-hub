<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Indicators;

use Illuminate\Support\Collection as BaseCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads the indicators the current filters leave as a spreadsheet-ready CSV.
 *
 * Money is never exported: only the indicator, its reading, its rating and the
 * report counts and donor names the page already shows.
 *
 * @property-read BaseCollection<int, array<string, mixed>> $filteredRows  #[Computed] filteredRows()
 */
trait ExportsIndicators
{
    public function exportIndicators(): StreamedResponse
    {
        $rows = $this->filteredRows;

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            // The byte-order mark lets Excel read Arabic correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_map($this->safeCell(...), [
                __('common.indicator_name'),
                __('indicators.result'),
                __('common.program'),
                __('indicators.measure'),
                __('indicators.baseline'),
                __('indicators.target'),
                __('indicators.latest'),
                __('indicators.last_reading'),
                __('indicators.percent_achieved'),
                __('indicators.rating'),
                __('common.targets'),
                __('indicators.targets_achieved'),
                __('indicators.reports_approved'),
                __('indicators.reports_total'),
                __('indicators.reports_overdue'),
                __('common.donors'),
            ]));

            foreach ($rows as $row) {
                $indicator = $row['indicator'];

                fputcsv($handle, array_map($this->safeCell(...), [
                    $indicator->name,
                    $indicator->result->statement,
                    $indicator->result->program?->name ?? __('common.unassigned'),
                    $indicator->measure->label(),
                    $row['baseline'],
                    $row['targetValue'],
                    $row['latest'],
                    $row['latestDate']?->toDateString(),
                    $row['achieved'] === null ? null : round($row['achieved'], 1),
                    $row['rag']?->label() ?? __('indicators.unrated'),
                    count($row['targets']),
                    $row['targetsAchieved'],
                    $row['approved'],
                    $row['reports'],
                    $row['overdueReports'],
                    implode('; ', array_column($row['funders'], 'label')),
                ]));
            }

            fclose($handle);
        }, 'indicators-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stops a cell that starts like a formula from running when opened in Excel.
     */
    protected function safeCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
