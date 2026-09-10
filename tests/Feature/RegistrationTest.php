<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private string $validPassword = 'D3or!s-Academe#2026';

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('homepage', absolute: false));
    }

    public function test_spa_json_register_creates_session(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $this->postJson('/api/register', [
            'name' => 'Spa User',
            'email' => 'spa@example.com',
            'password' => $this->validPassword,
            'password_confirmation' => $this->validPassword,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ])
            ->assertCreated()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.email', 'spa@example.com')
            ->assertJsonPath('user.role', 'student');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'spa@example.com',
            'role' => 'student',
        ]);
    }
}
