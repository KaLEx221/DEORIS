<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('homepage', absolute: false));
    }

    public function test_authenticated_dashboard_allows_entryease_frames(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get('/homepage');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $response->assertOk();
        $this->assertStringContainsString("frame-src 'self' https://entryease.onrender.com", $csp);
    }

    public function test_authenticated_root_uses_strict_csp_before_redirecting(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get('/');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $response->assertRedirect(route('homepage', absolute: false));
        $this->assertStringContainsString("frame-src 'self' https://entryease.onrender.com", $csp);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_spa_json_login_returns_user_payload(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);

        $this->assertAuthenticated();
    }
}
