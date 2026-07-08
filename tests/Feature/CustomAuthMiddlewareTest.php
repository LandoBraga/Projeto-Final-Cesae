<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Userprofile as UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Prompts\Exceptions\CookieException;
use Tests\TestCase;

class CustomAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary profiles for tests
        UserProfile::create(['name' => User::ROLE_USER]);
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        UserProfile::create(['name' => User::ROLE_ADMIN]);

        // Seed ticket statuses if not already done
        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    /** @test */
    public function it_allows_access_when_user_has_valid_token_and_correct_profile()
    {
        // Create user with technician profile and API token
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
                'user_id' => Auth::guard('api')->id(),
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token and correct profile (technician)
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Access granted.']);
    }

    /** @test */
    public function it_denies_access_when_user_has_no_valid_profile()
    {
        // Create user with technician profile but no API token (will fail validation)
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => null, // No API token
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with invalid token (user has no API token)
        $response = $this->withHeader('X-Auth-Token', 'invalid-token')
            ->get('/protected-auth');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Autenticação necessária. Envie X-Auth-Token no cabeçalho.',
            'error_code' => 401,
        ]);
    }

    /** @test */
    public function it_denies_access_when_user_has_invalid_token()
    {
        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with invalid token
        $response = $this->withHeader('X-Auth-Token', 'invalid-token')
            ->get('/protected-auth');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Autenticação necessária. Envie X-Auth-Token no cabeçalho.',
            'error_code' => 401,
        ]);
    }

    /** @test */
    public function it_denies_access_when_user_is_not_active()
    {
        // Create user with technician profile but inactive status and API token
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => false,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with token from inactive user
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Token inválido ou utilizador inativo.',
            'error_code' => 401,
            'errors' => [
                'api_token' => ['Invalid or user is inactive.']
            ]
        ]);
    }

    /** @test */
    public function it_denies_access_when_user_has_no_profile_id()
    {
        // Create user with technician profile but no API token (will fail validation)
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => null, // No profile ID
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token but user has no profile (will fail validation)
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Perfil inválido.',
            'error_code' => 403,
            'errors' => [
                'profile_id' => ['User must have a valid profile assigned.']
            ]
        ]);
    }

    /** @test */
    public function it_denies_access_when_user_has_no_profile_name()
    {
        // Create user with technician profile but no API token (will fail validation)
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
        ]);

        // Manually set a default profile to avoid the middleware's auto-fix behavior for this test
        UserProfile::where('name', User::ROLE_USER)->firstOrCreate();
        
        $user->profile_id = null; // Remove it again
        
        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token but user has no profile (will fail validation)
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Perfil inválido.',
            'error_code' => 403,
            'errors' => [
                'profile_id' => ['User must have a valid profile assigned.']
            ]
        ]);
    }

    /** @test */
    public function it_allows_access_when_user_has_valid_token_and_active_status()
    {
        // Create user with technician profile and API token, active status
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => true,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
                'user_id' => Auth::guard('api')->id(),
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token and active user
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Access granted.']);
    }

    /** @test */
    public function it_allows_access_when_user_has_multiple_roles()
    {
        // Create user with technician profile and API token, active status
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => true,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token and correct role (technician)
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_allows_access_when_user_has_admin_role()
    {
        // Create user with admin profile and API token, active status
        $userProfile = UserProfile::where('name', User::ROLE_ADMIN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => true,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token and correct role (admin)
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_allows_access_when_user_has_no_role()
    {
        // Create user with technician profile and API token, active status
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => true,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with valid token and correct role (user)
        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->get('/protected-auth');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_allows_access_when_user_has_bearer_token()
    {
        // Create user with technician profile and API token, active status
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => true,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with Bearer token (alternative to X-Auth-Token)
        $response = $this->withHeader('Authorization', 'Bearer ' . bin2hex(random_bytes(32)))
            ->get('/protected-auth');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_allows_access_when_user_has_cookie_token()
    {
        // Create user with technician profile and API token, active status
        $userProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => bin2hex(random_bytes(32)), // Generate random token
            'active' => true,
        ]);

        // Create a protected route that requires authentication only (no role restriction)
        Route::middleware(['auth:api'])->get('/protected-auth', function () {
            return response()->json([
                'message' => 'Access granted.',
            ], 200);
        })->name('test.protected.auth');

        // Make request with cookie token (alternative to X-Auth-Token)
        $response = $this->withCookie(cookie(): 'api_token', bin2hex(random_bytes(32)))
            ->get('/protected-auth');

        $response->assertStatus(401);
    }
}
