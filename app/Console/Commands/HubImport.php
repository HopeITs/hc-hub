<?php

namespace App\Console\Commands;

use App\Services\HubImport\HrImporter;
use App\Services\HubImport\ImportReport;
use Illuminate\Console\Command;

/**
 * Plan phase 3.1: copy the old apps' data into the hub. HR first (source
 * connection `hr_source`); tracking and media requests follow when their
 * modules are ported.
 *
 * Run it against a copy of the hub database, never the source. The source is
 * opened read-only; the report shows counts and ids only, never row values.
 */
class HubImport extends Command
{
    protected $signature = 'hub:import
        {--dry-run : Read and check everything, write nothing}';

    protected $description = 'Import hc-hr data into the hub (idempotent; ids kept, entity_xref written)';

    public function handle(HrImporter $importer): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? 'DRY RUN — nothing is written. ' : '')
            .'Source: '.config('database.connections.'.HrImporter::CONNECTION.'.database')
            .' → hub: '.config('database.connections.'.config('database.default').'.database'));

        $report = $importer->run($dryRun);
        $this->render($report);

        if ($report->blocking !== []) {
            $this->error('Blocked: nothing was written.');
            foreach ($report->blocking as $problem) {
                $this->line("  - {$problem}");
            }

            return self::FAILURE;
        }

        if (! $dryRun && ! $report->countsMatch()) {
            $this->error('Counts do not match the source (see the table above).');

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run finished.' : 'Import finished; counts match the source.');

        return self::SUCCESS;
    }

    private function render(ImportReport $report): void
    {
        $this->table(
            ['Table', 'Source', 'Skipped', 'Insert', 'Update', 'Prune', 'Expected', 'Hub after', ''],
            array_map(
                fn (string $table, array $counts): array => [
                    $table, $counts['source'], $counts['skipped'], $counts['inserted'], $counts['updated'],
                    $counts['pruned'], $counts['expected'], $counts['target'] ?? '-',
                    $counts['target'] === null ? '' : ($counts['target'] === $counts['expected'] ? 'ok' : 'MISMATCH'),
                ],
                array_keys($report->tables),
                $report->tables,
            ),
        );

        $this->section('Source tables not imported', array_map(
            fn (string $table, string $reason): string => "{$table}: {$reason}",
            array_keys($report->skippedSourceTables),
            $report->skippedSourceTables,
        ));

        $this->section('Source columns the hub does not have (dropped)', array_map(
            fn (string $table, array $columns): string => "{$table}: ".implode(', ', $columns),
            array_keys($report->droppedColumns),
            $report->droppedColumns,
        ));

        $skipped = [];
        foreach ($report->skippedRows as $table => $reasons) {
            foreach ($reasons as $reason => $count) {
                $skipped[] = "{$table}: {$count} — {$reason}";
            }
        }
        $this->section('Rows not imported', $skipped);

        $this->section('Morph types rewritten to aliases', $this->counted($report->morphRewrites));
        $this->section('Morph types with no alias (left as stored)', $this->counted($report->unmappedMorphTypes));
        $this->section('Seeded hub rows replaced by source rows', $report->replacedTargetRows);
        $this->section('Review decisions (written to review_decisions)', array_map(
            fn (array $decision): string => "{$decision['source_table']} #{$decision['source_id']} {$decision['column_name']}: {$decision['decision']} — {$decision['reason']}",
            $report->reviewDecisions,
        ));
        $this->section('Orphans in the source', $this->counted($report->orphans), 'none');
        $this->section('Open questions (not decided by the importer)', $report->openIssues);
    }

    /**
     * @param  list<string>  $lines
     */
    private function section(string $title, array $lines, ?string $whenEmpty = null): void
    {
        if ($lines === [] && $whenEmpty === null) {
            return;
        }

        $this->newLine();
        $this->line("<comment>{$title}</comment>");

        foreach ($lines === [] ? [(string) $whenEmpty] : $lines as $line) {
            $this->line("  - {$line}");
        }
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<string>
     */
    private function counted(array $counts): array
    {
        return array_map(
            fn (string $label, int $count): string => "{$label} ({$count})",
            array_keys($counts),
            $counts,
        );
    }
}
