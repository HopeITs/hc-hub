<?php

namespace Modules\Media\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Database\Seeders\MediaDemoSeeder;
use Tests\TestCase;

/**
 * On a server: no sample people. In the hub every account is an HR employee's
 * login, so media-request's own setup (media:admin, the People page) is not
 * ported; the media team and program managers come from HR contracts.
 */
class ServerSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeds_no_sample_people(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $users = User::count();

        $this->artisan('db:seed', ['--class' => MediaDemoSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertSame($users, User::count());
        $this->assertFalse(User::where('email', 'admin@example.test')->exists());
    }
}
