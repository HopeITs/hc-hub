<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen the high-risk PII columns to hold ciphertext, and add the blind index.
 *
 * This migration only changes the *shape* of the table. It deliberately does
 * not transform any data: converting plaintext to ciphertext is done by
 * `php artisan security:encrypt-pii`, which is resumable, verifies each row,
 * and can be run against a live database in batches. A migration that
 * rewrote every employee row in one transaction would be neither.
 *
 * Order matters on deploy:
 *   1. this migration (columns can hold both forms)
 *   2. deploy the code (models read plaintext or ciphertext transparently)
 *   3. security:encrypt-pii (converts the data)
 */
return new class extends Migration
{
    /**
     * Encrypted values are roughly 4x the plaintext plus ~200 bytes of
     * envelope, so short VARCHARs must become TEXT.
     */
    private const ENCRYPTED_COLUMNS = [
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

    public function up(): void
    {
        // The unique constraint moves from the plaintext column to the blind
        // index, because the ciphertext differs on every write and would let
        // the same number be stored twice.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['national_number']);
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (self::ENCRYPTED_COLUMNS as $column) {
                $table->text($column)->nullable()->change();
            }

            $table->string('national_number_index', 64)->nullable()->after('national_number');
            $table->unique('national_number_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['national_number_index']);
            $table->dropColumn('national_number_index');
        });

        // Rolling back the column types is only safe once the data has been
        // decrypted again; refuse rather than truncate somebody's PII.
        $encrypted = DB::table('employees')
            ->whereNotNull('national_number')
            ->where('national_number', 'like', 'eyJ%')
            ->exists();

        if ($encrypted) {
            throw new RuntimeException(
                'Employee PII is still encrypted. Run "php artisan security:encrypt-pii --decrypt" before rolling back this migration.',
            );
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('national_number')->nullable()->change();
            $table->unique('national_number');
        });
    }
};
