<?php

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The profile-settings feature from the Livewire starter kit was removed from
 * this app (only App\Livewire\Settings\Password remains; there is no
 * /settings/profile route or delete-account form). These starter-kit tests are
 * kept as skipped placeholders — delete this file if the feature is not
 * coming back.
 */
class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_settings_feature_was_removed(): void
    {
        $this->markTestSkipped('Profile settings were removed from this application; only password settings exist.');
    }
}
