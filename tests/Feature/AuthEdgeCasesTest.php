<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserProfile::create(['name' => User::ROLE_USER]);
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        UserProfile::create(['name' => User::ROLE_ADMIN]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $email = 'dup@example.com';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('password123'),
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'active' => true,
        ]);

        $response = $this->postJson('/register', [
            'name' => 'Dup User',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_login_rejects_inactive_user(): void
    {
        $user = User::factory()->create([
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'api_token' => Str::random(60),
            'active' => false,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Credenciais inválidas']);
    }

    public function test_login_replaces_api_token_and_invalidates_old_token(): void
    {
        $user = User::factory()->create([
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'active' => true,
            'password' => Hash::make('password123'),
            'api_token' => Str::random(60),
        ]);

        $oldToken = $user->api_token;

        $login = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $login->assertOk();
        $newToken = $login->json('token');
        $this->assertNotEquals($oldToken, $newToken);

        $logoutOld = $this->withHeader('X-Auth-Token', $oldToken)->postJson('/logout');
        $logoutOld->assertStatus(401);
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'active' => true,
            'password' => Hash::make('password123'),
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/password/change', [
                'current_password' => 'wrong-password',
                'new_password' => 'newpassword456',
            ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Password atual incorreta']);

        $user->refresh();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertFalse(Hash::check('newpassword456', $user->password));
    }

    public function test_password_change_requires_new_password_min_8(): void
    {
        $user = User::factory()->create([
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'active' => true,
            'password' => Hash::make('password123'),
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/password/change', [
                'current_password' => 'password123',
                'new_password' => 'short',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['new_password']]);
    }

    public function test_logout_clears_api_token_in_database(): void
    {
        $user = User::factory()->create([
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'active' => true,
            'password' => Hash::make('password123'),
            'api_token' => Str::random(60),
        ]);

        $this->assertNotNull($user->api_token);

        $logout = $this->withHeader('X-Auth-Token', $user->api_token)->postJson('/logout');
        $logout->assertOk();

        $user->refresh();
        $this->assertNull($user->api_token);
    }
}

