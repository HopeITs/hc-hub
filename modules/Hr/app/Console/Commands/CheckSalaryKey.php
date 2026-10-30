<?php

namespace Modules\Hr\Console\Commands;

use Modules\Hr\Services\SalaryCipher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reports whether this host can read the pay figures in its own database.
 *
 * Decryption failures are deliberately silent at runtime — a copy taken
 * without the key has to stay usable — which means a genuinely misconfigured
 * production box looks exactly like a working one until someone notices every
 * salary reads as blank. This is the command that tells them apart, and the
 * one to run after a deploy or a key rotation.
 */
class CheckSalaryKey extends Command
{
    protected $signature = 'salary:key:check {--sample=200 : Rows to test per table}';

    protected $description = 'Report whether stored pay figures are readable with the configured key';

    public function handle(SalaryCipher $cipher): int
    {
        $this->components->twoColumnDetail(
            'Key source',
            $cipher->usesDedicatedKey()
                ? '<info>SALARY_ENCRYPTION_KEY</info>'
                : '<comment>APP_KEY (fallback)</comment>',
        );

        $sample = max(1, (int) $this->option('sample'));

        $contracts = $this->probe($cipher, 'contracts', 'salary', $sample);
        $tiers = $this->probe($cipher, 'contract_pay_tiers', 'amount', $sample);

        $this->newLine();
        $this->report('contracts.salary', $contracts);
        $this->report('contract_pay_tiers.amount', $tiers);
        $this->newLine();

        $unreadable = $contracts['unreadable'] + $tiers['unreadable'];
        $plaintext = $contracts['plaintext'] + $tiers['plaintext'];

        if ($plaintext > 0) {
            $this->components->error(
                $plaintext.' figure(s) are still stored unencrypted. Run: php artisan migrate',
            );

            return self::FAILURE;
        }

        if ($unreadable > 0) {
            $this->components->error(
                $unreadable.' figure(s) could not be decrypted with the configured key.',
            );
            $this->line('  This host is holding the wrong key, or none. On a database copy that is');
            $this->line('  expected; on production it means the salaries are currently invisible.');

            return self::FAILURE;
        }

        if (! $cipher->usesDedicatedKey() && app()->isProduction()) {
            $this->components->warn(
                'Production is falling back to APP_KEY. Set SALARY_ENCRYPTION_KEY so that a '
                .'database copy taken with .env cannot read the salaries.',
            );

            return self::FAILURE;
        }

        $this->components->info('All sampled pay figures are readable.');

        return self::SUCCESS;
    }

    /**
     * @return array{total: int, readable: int, unreadable: int, plaintext: int}
     */
    private function probe(SalaryCipher $cipher, string $table, string $column, int $sample): array
    {
        $counts = ['total' => 0, 'readable' => 0, 'unreadable' => 0, 'plaintext' => 0];

        DB::table($table)
            ->select('id', $column)
            ->whereNotNull($column)
            ->orderByDesc('id')
            ->limit($sample)
            ->get()
            ->each(function ($row) use ($cipher, $column, &$counts): void {
                $value = (string) $row->{$column};

                if ($value === '') {
                    return;
                }

                $counts['total']++;

                if (! $cipher->looksEncrypted($value)) {
                    $counts['plaintext']++;

                    return;
                }

                $cipher->decrypt($value) === null
                    ? $counts['unreadable']++
                    : $counts['readable']++;
            });

        return $counts;
    }

    private function report(string $label, array $counts): void
    {
        if ($counts['total'] === 0) {
            $this->components->twoColumnDetail($label, '<comment>no rows</comment>');

            return;
        }

        $this->components->twoColumnDetail($label, sprintf(
            '%d sampled · <info>%d readable</info> · %d unreadable · %d plaintext',
            $counts['total'],
            $counts['readable'],
            $counts['unreadable'],
            $counts['plaintext'],
        ));
    }
}
