<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        // The root URL is the dashboard (auth-protected); guests are sent to login.
        $this->get('/')->assertRedirect('/login');

        $this->get('/login')->assertStatus(200);
    }
}
