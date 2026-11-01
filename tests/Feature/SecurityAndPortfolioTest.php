<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAndPortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default demo accounts
        User::create([
            'name' => 'Demo Admin',
            'username' => 'demo_admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('demo123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Demo Courier',
            'username' => 'demo_courier',
            'email' => 'courier@demo.com',
            'password' => Hash::make('demo123'),
            'role' => 'delivery',
            'employee_id' => 'RID-DEMO-01',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Demo Customer',
            'username' => 'demo_customer',
            'email' => 'customer@demo.com',
            'password' => Hash::make('demo123'),
            'role' => 'customer',
            'is_active' => true,
        ]);
    }

    public function test_login_with_valid_demo_admin_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@demo.com',
            'password' => 'demo123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'admin@demo.com',
                    'role' => 'admin',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_valid_demo_courier_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'courier@demo.com',
            'password' => 'demo123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'courier@demo.com',
                    'role' => 'delivery',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_valid_demo_customer_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'customer@demo.com',
            'password' => 'demo123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'customer@demo.com',
                    'role' => 'customer',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@demo.com',
            'password' => 'wrong_password_999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_deactivated_account_is_blocked_from_logging_in(): void
    {
        User::create([
            'name' => 'Suspended User',
            'username' => 'suspended_user',
            'email' => 'suspended@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_auth_me_endpoint_requires_valid_sanctum_token(): void
    {
        // Unauthenticated request should be rejected
        $unauth = $this->getJson('/api/auth/me');
        $unauth->assertStatus(401);

        // Authenticated request with Bearer token
        $user = User::where('email', 'customer@demo.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $auth = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $auth->assertStatus(200)
            ->assertJson([
                'user' => [
                    'email' => 'customer@demo.com',
                ],
            ]);
    }

    public function test_logout_revokes_current_access_token(): void
    {
        $user = User::where('email', 'admin@demo.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify token was deleted from DB
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token',
        ]);

        // Reset auth guard cache so the next request doesn't reuse in-memory authenticated user
        $this->app['auth']->forgetGuards();

        // Token should now be rejected by Sanctum
        $me = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $me->assertStatus(401);
    }
}
