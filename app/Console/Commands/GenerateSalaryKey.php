<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

/**
 * Mints the key that contract pay figures are encrypted under.
 *
 * Kept deliberately separate from `key:generate`: APP_KEY is rotated fairly
 * casually (a fresh clone, a reset environment), and rotating this one throws
 * away every salary in the database. Hence the refusal to overwrite an
 * existing key without --force, and the confirmation prompt when it is used.
 */
class GenerateSalaryKey extends Command
{
    protected $signature = 'salary:key:generate
        {--show : Print the key instead of writing it to .env}
        {--force : Overwrite a key that is already set}';

    protected $description = 'Generate the encryption key for contract pay figures';

    public function handle(): int
    {
        $key = 'base64:'.base64_encode(
            Encrypter::generateKey((string) config('salary.cipher', 'AES-256-CBC')),
        );

        if ($this->option('show')) {
            $this->line('<comment>'.$key.'</comment>');

            return self::SUCCESS;
        }

        $path = base_path('.env');

        if (! is_file($path)) {
            $this->error('No .env file found. Re-run with --show and set SALARY_ENCRYPTION_KEY by hand.');

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($path);
        $current = $this->currentKeyIn($contents);

        if ($current !== null && $current !== '' && ! $this->option('force')) {
            $this->error('SALARY_ENCRYPTION_KEY is already set.');
            $this->newLine();
            $this->warn('Replacing it makes every salary already in the database unreadable —');
            $this->warn('they are encrypted under the current key and nothing re-encrypts them.');
            $this->line('Back the current key up first, then re-run with --force if you are sure.');

            return self::FAILURE;
        }

        if ($current !== null && $current !== '' && $this->option('force')
            && ! $this->confirm('This will make existing salaries unreadable. Continue?', false)) {
            return self::FAILURE;
        }

        file_put_contents($path, $this->replaceKeyIn($contents, $key));

        $this->components->info('Salary encryption key set.');
        $this->newLine();
        $this->line(' Keep this key out of the .env you hand to developers, and back it up');
        $this->line(' somewhere other than the database — it is not recoverable.');

        return self::SUCCESS;
    }

    private function currentKeyIn(string $contents): ?string
    {
        return preg_match('/^SALARY_ENCRYPTION_KEY=(.*)$/m', $contents, $matches)
            ? trim($matches[1])
            : null;
    }

    private function replaceKeyIn(string $contents, string $key): string
    {
        if (preg_match('/^SALARY_ENCRYPTION_KEY=.*$/m', $contents)) {
            return preg_replace('/^SALARY_ENCRYPTION_KEY=.*$/m', 'SALARY_ENCRYPTION_KEY='.$key, $contents, 1);
        }

        return rtrim($contents, "\r\n")."\n\nSALARY_ENCRYPTION_KEY=".$key."\n";
    }
}
