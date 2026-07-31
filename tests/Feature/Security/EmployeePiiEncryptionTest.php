<?php

namespace Tests\Feature\Security;

use App\Models\Employee;
use App\Support\BlindIndex;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * The threat here is not a live attacker in the application — it is a copy of
 * the database leaving it: a stolen backup, an exposed replica, a laptop. For
 * staff of an NGO operating in Syria, a table of names, national ID numbers and
 * home addresses is the thing that gets people hurt.
 *
 * So every assertion below reads the *raw column*, not the model attribute.
 */
class EmployeePiiEncryptionTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_identifying_fields_are_ciphertext_in_the_database(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'pii-subject', [
            'national_number' => '01020304050',
            'address_en' => '12 Baron Street, Aleppo',
            'personal_phone' => '0900112233',
            'personal_email' => 'private@example.org',
        ]);

        $raw = $this->raw($employee);

        foreach (['01020304050', '12 Baron Street, Aleppo', '0900112233', 'private@example.org'] as $secret) {
            $this->assertStringNotContainsString(
                $secret,
                json_encode($raw),
                "Plaintext [{$secret}] is readable in the employees table",
            );
        }

        // And it really is decryptable, not merely mangled.
        $this->assertSame('01020304050', Crypt::decryptString($raw->national_number));
    }

    public function test_the_application_still_reads_the_values_transparently(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'pii-subject', [
            'national_number' => '01020304050',
            'address_en' => '12 Baron Street, Aleppo',
        ]);

        $reloaded = Employee::findOrFail($employee->id);

        $this->assertSame('01020304050', $reloaded->national_number);
        $this->assertSame('12 Baron Street, Aleppo', $reloaded->address_en);
    }

    public function test_the_same_value_encrypts_differently_for_two_employees(): void
    {
        $city = $this->makeCity('ALP', '001');

        $first = $this->makeEmployee($city, 'first', ['address_en' => 'Same Street 1']);
        $second = $this->makeEmployee($city, 'second', ['address_en' => 'Same Street 1']);

        // Non-deterministic encryption: an attacker with the table cannot tell
        // that these two people share an address.
        $this->assertNotSame(
            $this->raw($first)->address_en,
            $this->raw($second)->address_en,
        );
    }

    // --- Blind index -----------------------------------------------------

    public function test_the_national_number_is_still_unique(): void
    {
        $city = $this->makeCity('ALP', '001');
        $this->makeEmployee($city, 'first', ['national_number' => '01020304050']);

        // The unique constraint now lives on the blind index; a duplicate must
        // still be rejected even though the ciphertext differs.
        $this->expectException(UniqueConstraintViolationException::class);

        $this->makeEmployee($city, 'second', ['national_number' => '01020304050']);
    }

    public function test_the_national_number_can_still_be_looked_up_exactly(): void
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'findable', ['national_number' => '01020304050']);
        $this->makeEmployee($city, 'other', ['national_number' => '09080706050']);

        $found = Employee::query()->whereNationalNumber('01020304050')->first();

        $this->assertNotNull($found);
        $this->assertSame($employee->id, $found->id);
    }

    public function test_the_blind_index_does_not_reveal_the_value(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'indexed', [
            'national_number' => '01020304050',
        ]);

        $index = $this->raw($employee)->national_number_index;

        $this->assertNotNull($index);
        $this->assertSame(64, strlen($index));
        $this->assertStringNotContainsString('01020304050', $index);
    }

    public function test_formatting_differences_do_not_defeat_uniqueness(): void
    {
        // A number typed with spaces or Arabic-Indic digits is the same number
        // and must collide in the index, or the constraint silently stops
        // working the moment someone types it differently.
        $this->assertSame(BlindIndex::of('01020304050'), BlindIndex::of(' 0102 0304 050 '));
        $this->assertSame(BlindIndex::of('01020304050'), BlindIndex::of('٠١٠٢٠٣٠٤٠٥٠'));
        $this->assertNotSame(BlindIndex::of('01020304050'), BlindIndex::of('01020304051'));
    }

    public function test_updating_the_national_number_moves_the_index(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'mover', [
            'national_number' => '01020304050',
        ]);

        $employee->update(['national_number' => '09080706050']);

        $this->assertSame(
            BlindIndex::of('09080706050'),
            $this->raw($employee)->national_number_index,
        );
        $this->assertNull(Employee::query()->whereNationalNumber('01020304050')->first());
    }

    public function test_a_null_value_stays_null_rather_than_encrypting_the_empty_string(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'empty', [
            'national_number' => null,
            'address_en' => null,
        ]);

        $raw = $this->raw($employee);

        $this->assertNull($raw->national_number);
        $this->assertNull($raw->address_en);
        // Null must not occupy the unique index, or a second employee without
        // a national number could not be created.
        $this->assertNull($raw->national_number_index);
    }

    public function test_two_employees_without_a_national_number_can_coexist(): void
    {
        $city = $this->makeCity('ALP', '001');

        $this->makeEmployee($city, 'nobody-one', ['national_number' => null]);
        $this->makeEmployee($city, 'nobody-two', ['national_number' => null]);

        $this->assertSame(2, Employee::whereNull('national_number_index')->count());
    }

    public function test_plaintext_pii_reads_fail_closed_after_migration(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'plaintext-regression');
        DB::table('employees')->where('id', $employee->id)->update([
            'address_en' => 'accidentally stored plaintext',
        ]);

        config(['security.allow_plaintext_pii_reads' => false]);

        $this->expectException(DecryptException::class);
        Employee::findOrFail($employee->id)->address_en;
    }

    public function test_pii_verifier_detects_plaintext_without_printing_the_value(): void
    {
        $employee = $this->makeEmployee($this->makeCity('ALP', '001'), 'verifier-regression');
        DB::table('employees')->where('id', $employee->id)->update([
            'personal_phone' => 'SECRET-PHONE-VALUE',
        ]);

        $this->artisan('security:verify-pii')
            ->expectsOutputToContain('personal_phone is not valid ciphertext')
            ->doesntExpectOutputToContain('SECRET-PHONE-VALUE')
            ->assertFailed();
    }

    public function test_sensitive_lifecycle_columns_are_not_mass_assignable(): void
    {
        $employee = new Employee;

        $this->assertFalse($employee->isFillable('id'));
        $this->assertFalse($employee->isFillable('national_number_index'));
        $this->assertFalse($employee->isFillable('digital_id_token'));
        $this->assertFalse($employee->isFillable('digital_id_enabled'));
        $this->assertTrue($employee->isFillable('first_name_en'));
    }

    private function raw(Employee $employee): object
    {
        return DB::table('employees')->where('id', $employee->id)->first();
    }
}
