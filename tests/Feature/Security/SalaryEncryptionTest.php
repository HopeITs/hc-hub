<?php

namespace Tests\Feature\Security;

use App\Models\Contract;
use App\Models\ContractPayTier;
use App\Services\SalaryCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesContractFixtures;
use Tests\TestCase;

class SalaryEncryptionTest extends TestCase
{
    use CreatesContractFixtures, RefreshDatabase;

    public function test_a_salary_is_stored_as_ciphertext_and_reads_back_as_the_figure(): void
    {
        $contract = $this->contract(['salary' => 1500]);

        $stored = DB::table('contracts')->where('id', $contract->id)->value('salary');

        $this->assertNotSame('1500', (string) $stored);
        $this->assertStringNotContainsString('1500', (string) $stored);
        $this->assertTrue(app(SalaryCipher::class)->looksEncrypted($stored));

        $this->assertSame('1500.00', $contract->fresh()->salary);
    }

    public function test_a_pay_tier_amount_is_encrypted_too(): void
    {
        $contract = $this->contract();

        $tier = $contract->payTiers()->create([
            'condition_en' => '60h/month',
            'condition_ar' => '60 ساعة/شهر',
            'amount' => 200,
            'sort_order' => 0,
        ]);

        $stored = DB::table('contract_pay_tiers')->where('id', $tier->id)->value('amount');

        $this->assertTrue(app(SalaryCipher::class)->looksEncrypted($stored));
        $this->assertSame('200.00', ContractPayTier::query()->find($tier->id)->amount);
    }

    public function test_a_null_salary_stays_null_rather_than_encrypting_an_empty_string(): void
    {
        $contract = $this->contract(['salary' => null]);

        $this->assertNull(DB::table('contracts')->where('id', $contract->id)->value('salary'));
        $this->assertNull($contract->fresh()->salary);
    }

    /**
     * The central promise of the design: a copy of the database taken without
     * the key is unreadable, and unreadable is not the same as broken.
     */
    public function test_a_figure_written_under_another_key_reads_as_null_instead_of_throwing(): void
    {
        $contract = $this->contract(['salary' => 900]);

        $foreign = DB::table('contracts')->where('id', $contract->id)->value('salary');

        $this->rekey();

        $this->assertNull(Contract::query()->find($contract->id)->salary);
        $this->assertTrue(app(SalaryCipher::class)->looksEncrypted($foreign));
    }

    public function test_a_save_that_cannot_read_the_salary_does_not_erase_it(): void
    {
        $contract = $this->contract(['salary' => 900]);
        $ciphertext = DB::table('contracts')->where('id', $contract->id)->value('salary');

        $this->rekey();

        // What a developer on a copy of production does: open a contract, fix
        // something unrelated, save. The blank salary they were shown must not
        // travel back into the column.
        $onCopy = Contract::query()->find($contract->id);
        $this->assertNull($onCopy->salary);

        $onCopy->salary = null;
        $onCopy->weekly_hours = 35;
        $onCopy->save();

        $this->assertSame(
            $ciphertext,
            DB::table('contracts')->where('id', $contract->id)->value('salary'),
        );
        $this->assertSame(35, (int) DB::table('contracts')->where('id', $contract->id)->value('weekly_hours'));
    }

    public function test_a_readable_salary_can_still_be_cleared(): void
    {
        $contract = $this->contract(['salary' => 900]);

        $contract->salary = null;
        $contract->save();

        $this->assertNull(DB::table('contracts')->where('id', $contract->id)->value('salary'));
    }

    /**
     * Every encryption produces a new IV, so the raw column differs on every
     * write. Without EncryptedSalary::compare() the model would call that a
     * salary change on every single save.
     */
    public function test_rewriting_the_same_figure_is_not_a_change(): void
    {
        $contract = $this->contract(['salary' => 1200]);

        $contract = $contract->fresh();
        $contract->salary = 1200;

        $this->assertFalse($contract->isDirty('salary'));

        $contract->salary = 1300;

        $this->assertTrue($contract->isDirty('salary'));
    }

    public function test_looks_encrypted_rejects_plain_numbers_and_accepts_envelopes(): void
    {
        $cipher = app(SalaryCipher::class);

        $this->assertFalse($cipher->looksEncrypted('1500'));
        $this->assertFalse($cipher->looksEncrypted('1500.00'));
        $this->assertFalse($cipher->looksEncrypted(null));
        $this->assertFalse($cipher->looksEncrypted(''));
        $this->assertTrue($cipher->looksEncrypted($cipher->encrypt('1500.00')));
    }

    private function contract(array $overrides = []): Contract
    {
        $city = $this->makeCity('ALP', '001');

        return $this->makeContractFor(
            $this->makeEmployee($city, 'salary-encryption-'.uniqid()),
            $overrides,
        );
    }

    /** Swap in a different salary key, as a copied database would have. */
    private function rekey(): void
    {
        config(['salary.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->app->forgetInstance(SalaryCipher::class);
    }
}
