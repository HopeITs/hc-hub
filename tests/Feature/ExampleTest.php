<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_requires_authentication(): void
    {
        // The root URL is the dashboard (auth-protected); guests are sent to login.
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk();
    }
}
