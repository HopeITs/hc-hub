<?php

use Modules\Hr\Services\SalaryCipher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts the pay figures on a contract at rest.
 *
 * Two columns, not one: a fixed-pay contract keeps its figure in
 * contracts.salary, a flexible one keeps a list of them in
 * contract_pay_tiers.amount. Encrypting only the first would leave every
 * flexible contract's pay in plain sight.
 *
 * Both columns are widened to text before anything is written — the ciphertext
 * envelope is a couple of hundred characters and an INTEGER column would
 * silently truncate it to 0. The rows are then rewritten one by one through
 * the query builder rather than through Eloquent, so the new cast does not
 * encrypt a value this migration has already encrypted.
 *
 * Re-runnable: a row that already carries a ciphertext envelope is skipped, so
 * a run interrupted halfway can simply be repeated.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->columnTypeIsBinding()) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->text('salary')->nullable()->change();
            });

            Schema::table('contract_pay_tiers', function (Blueprint $table) {
                $table->text('amount')->nullable()->change();
            });
        }

        $cipher = app(SalaryCipher::class);

        $this->eachRow('contracts', 'salary', function (string $value) use ($cipher): ?string {
            return $cipher->looksEncrypted($value)
                ? null
                : $cipher->encrypt(number_format((float) $value, 2, '.', ''));
        });

        $this->eachRow('contract_pay_tiers', 'amount', function (string $value) use ($cipher): ?string {
            return $cipher->looksEncrypted($value)
                ? null
                : $cipher->encrypt(number_format((float) $value, 2, '.', ''));
        });
    }

    public function down(): void
    {
        $cipher = app(SalaryCipher::class);

        // Decrypt before narrowing the columns: a figure left as ciphertext
        // when the column becomes numeric is lost outright.
        $this->eachRow('contracts', 'salary', function (string $value) use ($cipher): ?string {
            $plain = $cipher->decrypt($value);

            return $plain === null ? null : (string) (int) round((float) $plain);
        });

        $this->eachRow('contract_pay_tiers', 'amount', function (string $value) use ($cipher): ?string {
            $plain = $cipher->decrypt($value);

            return $plain === null ? '0.00' : number_format((float) $plain, 2, '.', '');
        });

        if (! $this->columnTypeIsBinding()) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $table->integer('salary')->nullable()->change();
        });

        // The column was NOT NULL before this migration widened it; anything
        // that slipped in as null meanwhile has to become a number again
        // before the constraint can be restored.
        DB::table('contract_pay_tiers')->whereNull('amount')->update(['amount' => '0.00']);

        Schema::table('contract_pay_tiers', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });
    }

    /**
     * Does this connection actually need the columns widened?
     *
     * On SQLite it does not, and touching them is destructive. SQLite columns
     * carry a type *affinity*, not a type: a base64 envelope stored in an
     * INTEGER column is kept verbatim, because affinity conversion applies
     * only to text that is a well-formed number. Meanwhile SQLite has no ALTER
     * COLUMN, so Laravel implements change() by rebuilding the table -- and
     * dropping the old `contracts` fires contract_pay_tiers' ON DELETE
     * CASCADE, wiping every flexible contract's pay schedule. The type change
     * therefore runs only where it is both needed and safe: the servers with a
     * real ALTER TABLE. MySQL, which dev and production use, is one of them.
     */
    private function columnTypeIsBinding(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }

    /**
     * Walk a column, handing each non-empty value to $transform and writing
     * back whatever it returns. Returning null leaves the row untouched.
     *
     * Chunked by id rather than loaded whole: contracts is the largest table
     * in the system and this runs on the production box during a deploy.
     */
    private function eachRow(string $table, string $column, callable $transform): void
    {
        DB::table($table)
            ->select('id', $column)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $column, $transform): void {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    if ($value === null || $value === '') {
                        continue;
                    }

                    $replacement = $transform((string) $value);

                    if ($replacement === null) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update([$column => $replacement]);
                }
            });
    }
};
