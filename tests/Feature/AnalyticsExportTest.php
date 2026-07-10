<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        UserProfile::create(['name' => User::ROLE_USER]);
        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    public function test_technician_can_access_stats_charts_and_exports(): void
    {
        $techProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->firstOrFail();
        $userProfile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();

        $technician = User::factory()->create([
            'profile_id' => $techProfile->id,
            'api_token' => Str::random(60),
        ]);
        $creator = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);

        $closedStatusId = Ticket::getStatusIdByName(Ticket::STATUS_CLOSED);
        Ticket::create([
            'user_id' => $creator->id,
            'title' => 'Closed ticket',
            'description' => 'Analytics data',
            'status_id' => $closedStatusId,
            'opened_at' => now()->subDays(3),
            'closed_at' => now()->subDays(1),
            'minutes_spent' => 90,
            'cost' => 120.50,
        ]);

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/analytics')
            ->assertOk()
            ->assertJsonStructure(['average_resolution_minutes', 'average_waiting_minutes', 'open_tickets', 'closed_tickets']);

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/analytics/charts')
            ->assertOk()
            ->assertJsonStructure(['by_priority', 'monthly_tickets', 'monthly_cost', 'top_equipment']);

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->get('/analytics/export/csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->get('/analytics/export/pdf')
            ->assertOk();

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->get('/analytics/export/excel')
            ->assertOk();
    }

    public function test_technician_can_export_empty_reports_without_data(): void
    {
        $techProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->firstOrFail();
        $technician = User::factory()->create([
            'profile_id' => $techProfile->id,
            'api_token' => Str::random(60),
        ]);

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->get('/analytics/export/csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->get('/analytics/export/pdf')
            ->assertOk();

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->get('/analytics/export/excel')
            ->assertOk();
    }

    public function test_common_user_is_blocked_from_analytics_and_exports(): void
    {
        $userProfile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->getJson('/analytics')
            ->assertStatus(403);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->getJson('/analytics/charts')
            ->assertStatus(403);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->getJson('/analytics/export/csv')
            ->assertStatus(403);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->getJson('/analytics/export/pdf')
            ->assertStatus(403);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->getJson('/analytics/export/excel')
            ->assertStatus(403);
    }
}
