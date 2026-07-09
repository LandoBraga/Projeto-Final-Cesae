<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        \App\Models\UserProfile::create(['name' => User::ROLE_USER]);

        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    public function test_ticket_search_filters_by_keyword_priority_and_date_range(): void
    {
        $technicianProfile = \App\Models\UserProfile::where('name', User::ROLE_TECHNICIAN)->first();

        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);

        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);
        $closedStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CLOSED);

        Ticket::create([
            'user_id' => $technician->id,
            'title' => 'Motor compressor overheating',
            'description' => 'Issue on the main compressor motor.',
            'priority' => Ticket::PRIORITY_HIGH,
            'status_id' => $openStatusId,
            'opened_at' => now()->subDays(2),
        ]);

        Ticket::create([
            'user_id' => $technician->id,
            'title' => 'Light bulb replacement',
            'description' => 'Routine replacement.',
            'priority' => Ticket::PRIORITY_LOW,
            'status_id' => $closedStatusId,
            'opened_at' => now()->subDays(30),
            'closed_at' => now()->subDays(29),
        ]);

        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/tickets/search?q=compressor&priority=' . Ticket::PRIORITY_HIGH . '&date_from=' . now()->subDays(7)->toDateString());

        $response->assertOk();
        $response->assertJsonCount(1, 'tickets.data');
        $response->assertJsonPath('tickets.data.0.title', 'Motor compressor overheating');
    }

    public function test_ticket_search_returns_empty_results_when_no_match(): void
    {
        $technicianProfile = \App\Models\UserProfile::where('name', User::ROLE_TECHNICIAN)->first();

        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/tickets/search?q=this-should-not-match-anything');

        $response->assertOk();
        $response->assertJsonCount(0, 'tickets.data');
    }

    public function test_ticket_search_rejects_invalid_date_range(): void
    {
        $technicianProfile = \App\Models\UserProfile::where('name', User::ROLE_TECHNICIAN)->first();

        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/tickets/search?date_from=' . now()->toDateString() . '&date_to=' . now()->subDays(1)->toDateString());

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['date_to']]);
    }

    public function test_ticket_search_validates_priority_enum(): void
    {
        $technicianProfile = \App\Models\UserProfile::where('name', User::ROLE_TECHNICIAN)->first();

        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/tickets/search?priority=invalid-priority');

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['priority']]);
    }

}
