<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_return_to_a_job_after_registration(): void
    {
        $response = $this->post('/register', [
            'name' => 'Candidate User',
            'email' => 'candidate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'redirect' => '/jobs/product-designer?apply=1',
        ]);

        $response->assertRedirect('/jobs/product-designer?apply=1');
    }
}
