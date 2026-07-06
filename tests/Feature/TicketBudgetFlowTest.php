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

    public function test_technician_requests_budget_and_admin_approves()
    {
        // Cria utilizadores com papéis específicos para o cenário de teste:
        // - técnico (Emanuel)
        // - administrador (Gustavo)
        // - utilizador que cria o ticket
        // Os `api_token` são gerados para simular autenticação via header X-Auth-Token
        // create users
        $technician = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
            'api_token' => Str::random(60),
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'api_token' => Str::random(60),
        ]);

        $creator = User::factory()->create([
            'role' => User::ROLE_USER,
            'api_token' => Str::random(60),
        ]);

        // Cria um ticket como se fosse reportado por um utilizador comum
        // (estado inicial: aberta)
        // create ticket as creator
        $ticket = Ticket::create([
            'user_id' => $creator->id,
            'title' => 'Problema de teste',
            'description' => 'Descrição',
            'status' => Ticket::STATUS_OPEN,
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
            ]);

        $response->assertStatus(200);

        // Para testar a funcionalidade de pedido de orçamento, reposiciona o
        // ticket para `em curso` e invoca o endpoint `request-budget`.
        // request budget (should already be closed, so reopen flow: set in_progress again)
        $ticket->status = Ticket::STATUS_IN_PROGRESS;
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
