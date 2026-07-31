<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_employee_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('my-profile'))
            ->assertOk();
    }

    public function test_guest_cannot_view_an_employee_profile(): void
    {
        $this->get(route('my-profile'))
            ->assertRedirect(route('login'));
    }
}
