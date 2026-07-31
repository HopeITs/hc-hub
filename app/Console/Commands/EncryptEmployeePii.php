<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Support\BlindIndex;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Converts employee PII between plaintext and ciphertext.
 *
 * Written as a resumable command rather than a migration on purpose:
 *
 *  - It can be stopped and restarted. Each row is checked for its current
 *    state, so a run that dies halfway leaves a consistent database and the
 *    next run picks up exactly where it left off.
 *  - It verifies. Every row is read back and decrypted after writing; a
 *    mismatch aborts the batch rather than continuing to shred data.
 *  - It is reversible via --decrypt, which is what makes rolling the release
 *    back a real option instead of a restore-from-backup exercise.
 *  - It runs in batches against a live database instead of holding one
 *    transaction over the whole employees table.
 *
 * Deploy order: migrate (widens the columns) → deploy code → run this.
 */
class EncryptEmployeePii extends Command
{
    protected $signature = 'security:encrypt-pii
        {--decrypt : Reverse the conversion, for a rollback}
        {--chunk=200 : Rows per batch}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Encrypt (or decrypt) high-risk employee PII columns and rebuild the blind index';

    /** Must match the EncryptedPii casts on App\Models\Employee. */
    private const COLUMNS = [
        'national_number',
        'civil_registry_number',
        'civil_registry_office',
        'address_en',
        'address_ar',
        'personal_phone',
        'work_phone',
        'personal_email',
        'whatsapp_work',
        'telegram_work',
    ];

    public function handle(): int
    {
        $decrypt = (bool) $this->option('decrypt');
        $dryRun = (bool) $this->option('dry-run');
        $converted = 0;
        $skipped = 0;
        $failed = 0;

        $total = DB::table('employees')->count();
        $this->info(sprintf(
            '%s %d employee row(s).',
            $dryRun ? 'Inspecting' : ($decrypt ? 'Decrypting' : 'Encrypting'),
            $total,
        ));

        $bar = $this->output->createProgressBar($total);

        // Raw query builder throughout: going through Eloquent would apply the
        // casts and re-encrypt on save, which is the opposite of what a
        // controlled conversion needs.
        DB::table('employees')
            ->select(array_merge(['id', 'national_number_index'], self::COLUMNS))
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($rows) use ($decrypt, $dryRun, $bar, &$converted, &$skipped, &$failed): void {
                foreach ($rows as $row) {
                    $bar->advance();

                    try {
                        $updates = $this->convertRow($row, $decrypt);
                    } catch (Throwable $e) {
                        $failed++;
                        $this->newLine();
                        $this->error("Employee {$row->id}: {$e->getMessage()}");

                        continue;
                    }

                    if ($updates === []) {
                        $skipped++;

                        continue;
                    }

                    if ($dryRun) {
                        $converted++;

                        continue;
                    }

                    DB::transaction(function () use ($row, $updates, $decrypt): void {
                        DB::table('employees')->where('id', $row->id)->update($updates);

                        // Read back and prove the round trip before moving on.
                        $stored = DB::table('employees')->where('id', $row->id)->first();

                        foreach (self::COLUMNS as $column) {
                            if (! array_key_exists($column, $updates)) {
                                continue;
                            }

                            $value = $stored->{$column};

                            if ($value === null) {
                                continue;
                            }

                            $roundTrip = $decrypt ? $value : Crypt::decryptString($value);

                            abort_unless(
                                $roundTrip === $this->plaintextOf($row->{$column}),
                                500,
                                "Verification failed for employee {$row->id} column {$column}.",
                            );
                        }
                    });

                    $converted++;
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%s %d row(s); %d already in the target state; %d failed.',
            $dryRun ? 'Would convert' : 'Converted',
            $converted,
            $skipped,
            $failed,
        ));

        if ($failed > 0) {
            $this->error('Re-run after investigating the failures above. The command is safe to repeat.');

            return self::FAILURE;
        }

        if (! $dryRun && ! $decrypt) {
            $this->info('Run "php artisan security:verify-pii" to confirm no plaintext remains.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string|null> columns that need writing
     */
    private function convertRow(object $row, bool $decrypt): array
    {
        $updates = [];

        foreach (self::COLUMNS as $column) {
            $value = $row->{$column};

            if ($value === null || $value === '') {
                continue;
            }

            $isEncrypted = $this->isEncrypted($value);

            // Already in the state we want.
            if ($isEncrypted === ! $decrypt) {
                continue;
            }

            $updates[$column] = $decrypt
                ? Crypt::decryptString($value)
                : Crypt::encryptString($value);
        }

        // The index is derived from the plaintext either way, and is rebuilt on
        // every touched row so a partially-migrated table converges.
        $nationalNumber = $this->plaintextOf($row->national_number);
        $index = BlindIndex::of($nationalNumber);

        if (($row->national_number_index ?? null) !== $index) {
            $updates['national_number_index'] = $index;
        }

        return $updates;
    }

    private function plaintextOf(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->isEncrypted($value) ? Crypt::decryptString($value) : $value;
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
