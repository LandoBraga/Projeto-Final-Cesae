<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserProfile::create(['name' => User::ROLE_USER]);
    }

    public function test_user_can_register_login_change_password_and_logout(): void
    {
        $register = $this->postJson('/register', [
            'name' => 'Teacher Demo',
            'email' => 'demo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated();
        $register->assertJsonPath('user.email', 'demo@example.com');

        $user = User::where('email', 'demo@example.com')->firstOrFail();

        $login = $this->postJson('/login', [
            'email' => 'demo@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk();
        $login->assertJsonStructure(['user', 'token']);

        $token = $login->json('token');

        $change = $this->withHeader('X-Auth-Token', $token)->postJson('/password/change', [
            'current_password' => 'password123',
            'new_password' => 'password456',
        ]);

        $change->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('password456', $user->password));

        $logout = $this->withHeader('X-Auth-Token', $token)->postJson('/logout');
        $logout->assertOk();

        $user->refresh();
        $this->assertNull($user->api_token);
    }

    public function test_register_creates_default_profile_when_none_exists(): void
    {
        UserProfile::query()->delete();

        $response = $this->postJson('/register', [
            'name' => 'Fresh User',
            'email' => 'fresh@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'fresh@example.com')->firstOrFail();
        $this->assertNotNull($user->profile_id);
        $this->assertEquals(User::ROLE_USER, $user->profile->name);
    }

    public function test_login_does_not_require_csrf_token(): void
    {
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password123'),
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->value('id'),
            'active' => true,
            'api_token' => Str::random(60),
        ]);

        $response = $this->postJson('/login', [
            'email' => 'demo@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    public function test_register_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/register', [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short',
        ]);

        $response->assertStatus(422);
    }
}
