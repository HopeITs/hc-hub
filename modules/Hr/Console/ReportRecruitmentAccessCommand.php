<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Support\Recruitment\AccessComparison;

/**
 * The before / after table the owner reviews before recruitment starts
 * adding up the permissions of every role a person holds (roadmap 4.5, D5).
 * Read only: it changes nothing, and recruitment keeps deciding by the
 * active role until that review is done.
 *
 * Prints who would change and how; --csv writes one line per account
 * (changed or not) for the record.
 */
class ReportRecruitmentAccessCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hub:recruitment-access
        {--csv= : Also write every account to this CSV file}
        {--all : List the accounts that would not change too}';

    /**
     * @var string
     */
    protected $description = 'Compare recruitment access by active role (today) with the sum of all roles (read only)';

    public function handle(AccessComparison $comparison): int
    {
        $rows = $comparison->rows();
        $changed = array_values(array_filter($rows, fn (array $row): bool => $row['gained'] !== [] || $row['lost'] !== [] || $row['changes'] !== []));

        $this->components->info(count($rows).' account(s) hold a role or a direct recruitment permission; '.count($changed).' would change.');

        $listed = (bool) $this->option('all') ? $rows : $changed;

        if ($listed !== []) {
            $this->table(
                ['User', 'Roles', 'Acting as', 'Permissions', 'Passes every check', 'HR', 'Governorates', 'Reads pay'],
                array_map(fn (array $row): array => [
                    "#{$row['user_id']} {$row['name']}",
                    implode(', ', $row['roles']),
                    $row['active_role'] ?? '-',
                    count($row['before']['permissions']).' -> '.count($row['after']['permissions']),
                    $this->pair($row['before']['administrator'], $row['after']['administrator']),
                    $this->pair($row['before']['hr'], $row['after']['hr']),
                    $row['before']['cities'] === $row['after']['cities'] ? $row['before']['cities'] : "{$row['before']['cities']} -> {$row['after']['cities']}",
                    $this->pair($row['before']['salary'], $row['after']['salary']),
                ], $listed),
            );
        }

        $csv = $this->option('csv');

        if (is_string($csv) && $csv !== '') {
            $this->writeCsv($csv, $rows);
            $this->line("Written: {$csv}");
        }

        $this->newLine();
        $this->components->info('Nothing was changed.');

        return self::SUCCESS;
    }

    private function pair(bool $before, bool $after): string
    {
        $word = fn (bool $value): string => $value ? 'yes' : 'no';

        return $before === $after ? $word($before) : $word($before).' -> '.$word($after);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->components->error("Cannot write {$path}");

            return;
        }

        fputcsv($handle, [
            'user_id', 'name', 'email', 'employee_id', 'roles', 'acting_as', 'changes',
            'permissions_before', 'permissions_after', 'gained', 'lost',
            'administrator_before', 'administrator_after', 'hr_before', 'hr_after',
            'governorates_before', 'governorates_after', 'reads_pay_before', 'reads_pay_after',
        ]);

        foreach ($rows as $row) {
            $changed = $row['gained'] !== [] || $row['lost'] !== [] || $row['changes'] !== [];

            fputcsv($handle, [
                $row['user_id'], $row['name'], $row['email'], $row['employee_id'], implode(' | ', $row['roles']), $row['active_role'],
                $changed ? 'yes' : 'no',
                count($row['before']['permissions']), count($row['after']['permissions']),
                implode(' ', $row['gained']), implode(' ', $row['lost']),
                (int) $row['before']['administrator'], (int) $row['after']['administrator'],
                (int) $row['before']['hr'], (int) $row['after']['hr'],
                $row['before']['cities'], $row['after']['cities'],
                (int) $row['before']['salary'], (int) $row['after']['salary'],
            ]);
        }

        fclose($handle);
    }
}
