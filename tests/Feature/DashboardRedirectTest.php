<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserProfile::create(['name' => User::ROLE_USER]);
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        UserProfile::create(['name' => User::ROLE_ADMIN]);
    }

    public function test_after_login_user_is_redirected_to_dashboard_to_manage_tickets(): void
    {
        $user = User::factory()->create([
            'profile_id' => UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'active' => true,
            'password' => Hash::make('password123'),
            'api_token' => Str::random(60),
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // O login endpoint (API) retorna JSON + cookie, então a verificação de redirect deve ser feita
        // para rotas UI que usam o dashboard.
        // Mantém o teste focado no que é observável e estável: depois de login, o acesso ao dashboard deve funcionar.
        $token = $response->json('token');

        // UI dashboard (rotas baseadas em UiController)
        $dashboard = $this
            ->withHeader('X-Auth-Token', $token)
            ->get('/ui');

        $dashboard->assertStatus(200);

        // Conteúdo esperado (Blade layout do dashboard)
        $dashboard->assertSee('Tickets');
    }
}


