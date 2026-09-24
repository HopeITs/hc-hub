<?php

namespace Tests\Feature\Security;

use App\Services\SalaryCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesContractFixtures;
use Tests\TestCase;

/**
 * salary:key:check is the only thing that tells a correctly configured host
 * apart from one whose salaries silently read as blank, because decryption
 * failures are deliberately not errors at runtime.
 */
class SalaryKeyCommandTest extends TestCase
{
    use CreatesContractFixtures, RefreshDatabase;

    public function test_the_check_passes_when_the_figures_are_readable(): void
    {
        $this->contract(['salary' => 1500]);

        $this->artisan('salary:key:check')
            ->expectsOutputToContain('readable')
            ->assertExitCode(0);
    }

    public function test_the_check_fails_when_the_host_holds_the_wrong_key(): void
    {
        $this->contract(['salary' => 1500]);

        config(['salary.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->app->forgetInstance(SalaryCipher::class);

        $this->artisan('salary:key:check')->assertExitCode(1);
    }

    public function test_the_check_fails_when_a_figure_is_still_stored_in_plaintext(): void
    {
        $contract = $this->contract(['salary' => 1500]);

        // A row the migration has not reached yet.
        DB::table('contracts')->where('id', $contract->id)->update(['salary' => '1500']);

        $this->artisan('salary:key:check')->assertExitCode(1);
    }

    public function test_generating_a_key_refuses_to_overwrite_one_that_is_already_set(): void
    {
        $original = $this->app->basePath();
        $sandbox = $this->sandboxWithEnv("APP_ENV=testing\nSALARY_ENCRYPTION_KEY=base64:existing\n");

        try {
            $this->app->setBasePath($sandbox);

            $this->artisan('salary:key:generate')->assertExitCode(1);

            $this->assertStringContainsString(
                'SALARY_ENCRYPTION_KEY=base64:existing',
                file_get_contents($sandbox.'/.env'),
            );
        } finally {
            $this->app->setBasePath($original);
        }
    }

    public function test_force_replaces_the_key_in_the_env_file(): void
    {
        $original = $this->app->basePath();
        $sandbox = $this->sandboxWithEnv("APP_ENV=testing\nSALARY_ENCRYPTION_KEY=base64:existing\n");

        try {
            $this->app->setBasePath($sandbox);

            $this->artisan('salary:key:generate --force')
                ->expectsConfirmation('This will make existing salaries unreadable. Continue?', 'yes')
                ->assertExitCode(0);

            $contents = file_get_contents($sandbox.'/.env');

            $this->assertStringNotContainsString('base64:existing', $contents);
            $this->assertMatchesRegularExpression('/^SALARY_ENCRYPTION_KEY=base64:\S+$/m', $contents);
        } finally {
            $this->app->setBasePath($original);
        }
    }

    public function test_show_prints_a_key_without_touching_the_env_file(): void
    {
        $this->artisan('salary:key:generate --show')->assertExitCode(0);
    }

    /** A throwaway base path: the project's own .env is not a test fixture. */
    private function sandboxWithEnv(string $contents): string
    {
        $path = sys_get_temp_dir().'/salary-key-'.uniqid();

        mkdir($path);
        file_put_contents($path.'/.env', $contents);

        return $path;
    }

    private function contract(array $overrides = [])
    {
        $city = $this->makeCity('ALP', '001');

        return $this->makeContractFor(
            $this->makeEmployee($city, 'salary-key-'.uniqid()),
            $overrides,
        );
    }
}
