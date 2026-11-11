<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Support\Import\HrImporter;
use Modules\Hr\Support\Import\OrphanCheck;
use Modules\Hr\Support\Import\UserIdRemap;
use Throwable;

/**
 * Copy the HR app's database into the hub. Safe to run again until launch:
 * HR-owned tables are reloaded, Core and shared tables are upserted, and the
 * renumbering of tracking users only touches users not yet on their HR id.
 * See HrImporter for what happens to each table.
 */
class ImportHrCommand extends Command
{
    protected $signature = 'hub:import-hr
        {--source=hr_import : The database connection holding the HR app\'s tables}';

    protected $description = 'Import the HR app\'s data into the hub (re-runnable until launch)';

    public function handle(): int
    {
        $source = (string) $this->option('source');
        $pending = UserIdRemap::make()->pending();

        $this->components->info($pending === 0
            ? 'Users already carry their HR ids; no renumbering needed.'
            : "Renumbering {$pending} users to their HR ids.");

        try {
            $result = HrImporter::from($source)->run();
        } catch (Throwable $exception) {
            $this->components->error('Import failed and was rolled back: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Table', 'Source rows', 'Target rows', 'Changed', 'Note'],
            array_map(fn (array $row): array => [
                $row['table'],
                $row['source'] ?? '-',
                $row['target'],
                $row['changed'],
                $row['note'],
            ], $result['summary']),
        );

        if ($result['remap']['users'] > 0) {
            $this->components->info("Renumbered {$result['remap']['users']} users; rows rewritten:");
            $this->components->bulletList(array_map(
                fn (string $column, int $rows): string => "{$column}: {$rows}",
                array_keys(array_filter($result['remap']['rows'])),
                array_values(array_filter($result['remap']['rows'])),
            ));
        }

        $dangling = OrphanCheck::make()->userReferences();

        if ($dangling !== []) {
            $this->components->warn('Unconstrained user references with no user: '.json_encode($dangling));
        }

        $mismatched = array_filter($result['summary'], fn (array $row): bool => $row['source'] !== null && $row['target'] < $row['source']);

        if ($mismatched !== []) {
            $this->components->warn('Fewer target than source rows in: '.implode(', ', array_column($mismatched, 'table')));
        }

        $this->components->info('HR import complete.');

        return self::SUCCESS;
    }
}
