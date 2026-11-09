<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Attributes\Seeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

/**
 * Every Feature test starts from the seeded access-control catalog: the
 * permissions and roles exist exactly as config/access-control.php defines
 * them (seeded once per run by RefreshDatabase), so a test only has to pick
 * the role it acts as.
 */
#[Seeder(RolesAndPermissionsSeeder::class)]
abstract class TestCase extends BaseTestCase
{
    /**
     * Refuses to run against anything but a test database. A cached config
     * (bootstrap/cache/config.php) makes Laravel ignore phpunit.xml, and
     * RefreshDatabase then rebuilds the real development database, wiping it.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get('database.connections.'.$connection.'.database');

        if ($app->configurationIsCached() || ! ($database === ':memory:' || str_ends_with($database, '_test'))) {
            throw new RuntimeException("Tests stopped: they would run against the [{$database}] database. Run `php artisan config:clear` so phpunit.xml's test database is used.");
        }

        return $app;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
