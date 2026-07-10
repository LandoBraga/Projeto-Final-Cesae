<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketBudgetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar perfis necessários para os testes
        \App\Models\UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        \App\Models\UserProfile::create(['name' => User::ROLE_ADMIN]);
        \App\Models\UserProfile::create(['name' => User::ROLE_USER]);

        // Criar estados de ticket
        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    public function test_technician_requests_budget_and_admin_approves()
    {
        // Cria utilizadores com papéis específicos para o cenário de teste:
        // - técnico (Emanuel)
        // - administrador (Gustavo)
        // - utilizador que cria o ticket
        // Os `api_token` são gerados para simular autenticação via header X-Auth-Token
        // create users
        $technicianProfile = \App\Models\UserProfile::where('name', User::ROLE_TECHNICIAN)->first();
        $adminProfile = \App\Models\UserProfile::where('name', User::ROLE_ADMIN)->first();
        $userProfile = \App\Models\UserProfile::where('name', User::ROLE_USER)->first();

        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);

        $admin = User::factory()->create([
            'profile_id' => $adminProfile->id,
            'api_token' => Str::random(60),
        ]);

        $creator = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);

        // Cria um ticket como se fosse reportado por um utilizador comum
        // (estado inicial: aberta)
        // create ticket as creator
        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);
        $ticket = Ticket::create([
            'user_id' => $creator->id,
            'title' => 'Problema de teste',
            'description' => 'Descrição',
            'status_id' => $openStatusId,
            'opened_at' => now(),
        ]);

        // Técnico inicia a reparação (rota /start). Isto passa `status` para `em curso`.
        // technician starts the repair
        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->putJson('/technician/tickets/'.$ticket->id.'/start');

        $response->assertStatus(200);

        // Técnico encerra provisoriamente com um custo alto. No fluxo real,
        // um técnico pode depois pedir autorização de orçamento se o custo
        // exceder o threshold configurado.
        // technician closes with high cost triggers budget request
        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->putJson('/technician/tickets/'.$ticket->id.'/close', [
                'minutes_spent' => 60,
                'cost' => 500.00,
                'report' => 'Peças substituídas e validação concluída.',
            ]);

        $response->assertStatus(200);

        // Para testar a funcionalidade de pedido de orçamento, reposiciona o
        // ticket para `em curso` e invoca o endpoint `request-budget`.
        // request budget (should already be closed, so reopen flow: set in_progress again)
        $inProgressStatusId = Ticket::getStatusIdByName(Ticket::STATUS_IN_PROGRESS);
        $ticket->status_id = $inProgressStatusId;
        $ticket->save();

        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->putJson('/technician/tickets/'.$ticket->id.'/request-budget', [
                'threshold' => 100.00,
            ]);

        $response->assertStatus(200);
        $ticket->refresh();
        $this->assertTrue($ticket->budget_requested);
        $this->assertEquals(Ticket::BUDGET_PENDING, $ticket->budget_status);

        // ADM aprova o orçamento pendente através do endpoint de administração
        // admin approves
        $response = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->patchJson('/admin/tickets/'.$ticket->id.'/approve-budget');

        $response->assertStatus(200);
        $ticket->refresh();
        $this->assertEquals(Ticket::BUDGET_APPROVED, $ticket->budget_status);
        $this->assertEquals($admin->id, $ticket->budget_approved_by);
    }
}
