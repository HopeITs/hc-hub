<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Hr\Services\SalaryCipher;
use Tests\TestCase;

class EncryptContractPayFiguresMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_encrypts_the_plaintext_figures_already_in_the_table(): void
    {
        $migration = $this->migrationInstance();
        $migration->down();

        $this->seedPlaintextRows();

        $migration->up();

        $cipher = app(SalaryCipher::class);

        $salary = DB::table('contracts')->where('id', 801)->value('salary');
        $amount = DB::table('contract_pay_tiers')->where('id', 811)->value('amount');

        $this->assertTrue($cipher->looksEncrypted($salary));
        $this->assertSame('1500.00', $cipher->decrypt($salary));

        $this->assertTrue($cipher->looksEncrypted($amount));
        $this->assertSame('200.00', $cipher->decrypt($amount));

        // A contract with no figure keeps none rather than gaining ciphertext
        // for an empty string.
        $this->assertNull(DB::table('contracts')->where('id', 802)->value('salary'));
    }

    /**
     * A run interrupted halfway has to be repeatable: re-encrypting a
     * ciphertext would leave a value nothing can read back.
     */
    public function test_up_is_re_runnable_over_already_encrypted_rows(): void
    {
        $migration = $this->migrationInstance();
        $migration->down();
        $this->seedPlaintextRows();

        $migration->up();
        $afterFirstRun = DB::table('contracts')->where('id', 801)->value('salary');

        $migration->up();

        $this->assertSame($afterFirstRun, DB::table('contracts')->where('id', 801)->value('salary'));
        $this->assertSame('1500.00', app(SalaryCipher::class)->decrypt($afterFirstRun));
    }

    public function test_down_restores_readable_numbers(): void
    {
        $migration = $this->migrationInstance();
        $migration->down();
        $this->seedPlaintextRows();
        $migration->up();

        $migration->down();

        $this->assertSame(1500, (int) DB::table('contracts')->where('id', 801)->value('salary'));
        $this->assertSame(200.0, (float) DB::table('contract_pay_tiers')->where('id', 811)->value('amount'));
    }

    /**
     * The point of the widening: whatever the driver, the column has to hand
     * back the exact envelope it was given. (On SQLite the migration leaves
     * the declared type alone -- see columnTypeIsBinding() -- so this is the
     * assertion that proves affinity really does keep the string verbatim.)
     */
    public function test_the_column_returns_a_ciphertext_envelope_verbatim(): void
    {
        $this->assertTrue(Schema::hasColumn('contracts', 'salary'));

        $payload = app(SalaryCipher::class)->encrypt('1500.00');

        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('contracts')->insert($this->contractRow(803, ['salary' => $payload]));

        $this->assertSame($payload, DB::table('contracts')->where('id', 803)->value('salary'));
    }

    /**
     * The cascade that made the SQLite path destructive: a contracts table
     * rebuild deletes every contract_pay_tiers row through ON DELETE CASCADE.
     */
    public function test_up_does_not_delete_the_pay_tiers_it_is_encrypting(): void
    {
        $migration = $this->migrationInstance();
        $migration->down();
        $this->seedPlaintextRows();

        $migration->up();

        $this->assertDatabaseCount('contract_pay_tiers', 1);
    }

    private function seedPlaintextRows(): void
    {
        // See RestructureContractsScheduleAndPayMigrationTest for why this is
        // PRAGMA defer_foreign_keys rather than
        // Schema::withoutForeignKeyConstraints(): the latter is a documented
        // no-op once RefreshDatabase's per-test transaction is already open.
        DB::statement('PRAGMA defer_foreign_keys = ON');

        DB::table('contracts')->insert([
            $this->contractRow(801, ['salary' => 1500]),
            $this->contractRow(802, ['salary' => null]),
        ]);

        DB::table('contract_pay_tiers')->insert([
            [
                'id' => 811,
                'contract_id' => 801,
                'condition_en' => '60h/month',
                'condition_ar' => '60h/month',
                'amount' => 200,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function contractRow(int $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'employee_id' => 1,
            'position_id' => 1,
            'contract_type_id' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'currency' => 2,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }

    private function migrationInstance(): Migration
    {
        return require base_path('modules/Hr/database/migrations/2026_09_22_000000_encrypt_contract_pay_figures.php');
    }
}
