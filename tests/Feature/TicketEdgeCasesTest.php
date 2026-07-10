<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $technician;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\UserProfile::create(['name' => User::ROLE_USER]);
        \App\Models\UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        \App\Models\UserProfile::create(['name' => User::ROLE_ADMIN]);

        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);

        $this->user = User::factory()->create([
            'profile_id' => \App\Models\UserProfile::where('name', User::ROLE_USER)->firstOrFail()->id,
            'api_token' => Str::random(60),
        ]);

        $this->technician = User::factory()->create([
            'profile_id' => \App\Models\UserProfile::where('name', User::ROLE_TECHNICIAN)->firstOrFail()->id,
            'api_token' => Str::random(60),
        ]);

        $this->admin = User::factory()->create([
            'profile_id' => \App\Models\UserProfile::where('name', User::ROLE_ADMIN)->firstOrFail()->id,
            'api_token' => Str::random(60),
        ]);
    }

    private function createTicketWithStatus(string $statusName, array $overrides = []): Ticket
    {
        $statusId = Ticket::getStatusIdByName($statusName);

        return Ticket::create(array_merge([
            'user_id' => $this->user->id,
            'title' => 'Ticket edge case',
            'description' => 'Edge case',
            'status_id' => $statusId,
            'opened_at' => now(),
        ], $overrides));
    }

    public function test_upload_photo_rejects_when_file_exceeds_max_2048(): void
    {
        Storage::fake('public');

        $ticket = $this->createTicketWithStatus(Ticket::STATUS_OPEN, [
            'opened_at' => now(),
        ]);

        $response = $this->withHeader('X-Auth-Token', $this->user->api_token)
            ->postJson('/tickets/' . $ticket->id . '/photos', [
                'photo' => UploadedFile::fake()->create('big.jpg', 3000, 'image/jpeg'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['photo']]);
    }

    public function test_close_ticket_rejects_when_status_is_not_in_progress(): void
    {
        $ticket = $this->createTicketWithStatus(Ticket::STATUS_OPEN);

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->putJson('/technician/tickets/' . $ticket->id . '/close', [
                'minutes_spent' => 10,
                'cost' => 10.5,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Só é possível encerrar tickets em curso']);
    }

    public function test_close_ticket_rejects_when_minutes_spent_is_invalid(): void
    {
        $inProgress = $this->createTicketWithStatus(Ticket::STATUS_IN_PROGRESS);

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->putJson('/technician/tickets/' . $inProgress->id . '/close', [
                'minutes_spent' => 0,
                'cost' => 10.5,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['minutes_spent']]);
    }



    public function test_request_budget_returns_message_when_not_required_and_threshold_is_high_enough(): void
    {
        // Ticket sem custo e com cost no payload -> passa em validacao e faz requested=false
        $inProgress = $this->createTicketWithStatus(Ticket::STATUS_IN_PROGRESS, [
            'cost' => 5.0,
            'budget_requested' => false,
        ]);

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->putJson('/technician/tickets/' . $inProgress->id . '/request-budget', [
                'threshold' => 1000,
            ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Não foi necessário pedir autorização de orçamento']);
    }

    public function test_schedule_ticket_rejects_when_end_is_before_start(): void
    {
        $ticket = $this->createTicketWithStatus(Ticket::STATUS_IN_PROGRESS);

        $start = now()->addDays(2)->toDateTimeString();
        $end = now()->addDay()->toDateTimeString();

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->postJson('/tickets/' . $ticket->id . '/schedule', [
                'start' => $start,
                'end' => $end,
                'technician_id' => $this->technician->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }

    public function test_schedule_ticket_rejects_when_technician_id_does_not_exist(): void
    {
        $ticket = $this->createTicketWithStatus(Ticket::STATUS_OPEN);

        $response = $this->withHeader('X-Auth-Token', $this->admin->api_token)
            ->postJson('/tickets/' . $ticket->id . '/schedule', [
                'start' => now()->addDay()->toDateTimeString(),
                'end' => now()->addDay()->addHours(1)->toDateTimeString(),
                'technician_id' => 999999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['technician_id']]);
    }

    public function test_closed_ticket_can_be_reopened_and_cannot_be_reopened_twice(): void
    {
        $closedTicket = $this->createTicketWithStatus(Ticket::STATUS_CLOSED);

        $firstReopen = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->postJson('/tickets/' . $closedTicket->id . '/reopen');

        $firstReopen->assertOk();
        $closedTicket->refresh();
        $this->assertTrue($closedTicket->hasStatus(Ticket::STATUS_OPEN));

        $secondReopen = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->postJson('/tickets/' . $closedTicket->id . '/reopen');

        $secondReopen->assertStatus(422);
        $secondReopen->assertJson(['message' => 'Só é possível reabrir tickets fechados']);
    }

    public function test_common_user_can_cancel_only_open_tickets(): void
    {
        $ticket = $this->createTicketWithStatus(Ticket::STATUS_OPEN);

        $response = $this->withHeader('X-Auth-Token', $this->user->api_token)
            ->postJson('/tickets/' . $ticket->id . '/cancel');

        $response->assertOk();
        $ticket->refresh();
        $this->assertTrue($ticket->hasStatus('cancelada'));
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_close_ticket_requires_final_report_before_closing(): void
    {
        $inProgress = $this->createTicketWithStatus(Ticket::STATUS_IN_PROGRESS);

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->putJson('/technician/tickets/' . $inProgress->id . '/close', [
                'minutes_spent' => 30,
                'cost' => 25.5,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.report.0', 'O relatório técnico final é obrigatório.');

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->putJson('/technician/tickets/' . $inProgress->id . '/close', [
                'minutes_spent' => 30,
                'cost' => 25.5,
                'report' => 'Substituição de periférico concluída.',
            ]);

        $response->assertOk();
        $inProgress->refresh();
        $this->assertEquals('Substituição de periférico concluída.', $inProgress->technical_report);
        $this->assertNotNull($inProgress->closed_at);
    }

    public function test_budget_request_marks_pending_status_and_admin_rejection_updates_it(): void
    {
        $inProgress = $this->createTicketWithStatus(Ticket::STATUS_IN_PROGRESS, [
            'cost' => 250.0,
            'budget_requested' => false,
        ]);

        $response = $this->withHeader('X-Auth-Token', $this->technician->api_token)
            ->putJson('/technician/tickets/' . $inProgress->id . '/request-budget', [
                'threshold' => 100.0,
            ]);

        $response->assertOk();
        $inProgress->refresh();
        $this->assertTrue($inProgress->hasStatus('pendente orçamento'));
        $this->assertTrue($inProgress->budget_requested);

        $response = $this->withHeader('X-Auth-Token', $this->admin->api_token)
            ->patchJson('/admin/tickets/' . $inProgress->id . '/approve-budget', [
                'decision' => 'reject',
                'feedback' => 'Orçamento acima do limite aprovado.',
            ]);

        $response->assertOk();
        $inProgress->refresh();
        $this->assertTrue($inProgress->hasStatus('recusada'));
        $this->assertEquals(Ticket::BUDGET_REJECTED, $inProgress->budget_status);
    }
}

