<?php

namespace App\Console\Commands;

use App\Support\BlindIndex;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class VerifyEmployeePii extends Command
{
    protected $signature = 'security:verify-pii {--chunk=200 : Rows per batch}';

    protected $description = 'Verify that employee PII is encrypted and blind indexes match';

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
        $failures = 0;
        $checked = 0;

        DB::table('employees')
            ->select(array_merge(['id', 'national_number_index'], self::COLUMNS))
            ->orderBy('id')
            ->chunkById(max(1, (int) $this->option('chunk')), function ($rows) use (&$failures, &$checked): void {
                foreach ($rows as $row) {
                    $checked++;
                    $nationalNumber = null;

                    foreach (self::COLUMNS as $column) {
                        $stored = $row->{$column};
                        if ($stored === null || $stored === '') {
                            continue;
                        }

                        try {
                            $plaintext = Crypt::decryptString($stored);
                        } catch (DecryptException) {
                            $failures++;
                            $this->error("Employee {$row->id}: {$column} is not valid ciphertext.");

                            continue;
                        }

                        if ($column === 'national_number') {
                            $nationalNumber = $plaintext;
                        }
                    }

                    if (! hash_equals(
                        (string) ($row->national_number_index ?? ''),
                        (string) (BlindIndex::of($nationalNumber) ?? ''),
                    )) {
                        $failures++;
                        $this->error("Employee {$row->id}: national number blind index does not match.");
                    }
                }
            });

        if ($failures > 0) {
            $this->error("PII verification failed: {$failures} problem(s) across {$checked} row(s).");

            return self::FAILURE;
        }

        $this->info("PII verification passed for {$checked} employee row(s).");

        return self::SUCCESS;
    }
}
