<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Models\Userprofile as UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserProfile::create(['name' => User::ROLE_USER]);
        UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        UserProfile::create(['name' => User::ROLE_ADMIN]);
        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    public function test_technician_can_fetch_dashboard_metrics(): void
    {
        $techProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->firstOrFail();
        $technician = User::factory()->create([
            'profile_id' => $techProfile->id,
            'api_token' => Str::random(60),
        ]);

        $openStatusId = TicketStatus::where('name', Ticket::STATUS_OPEN)->value('id');
        $closedStatusId = TicketStatus::where('name', Ticket::STATUS_CLOSED)->value('id');
        $inProgressStatusId = TicketStatus::where('name', Ticket::STATUS_IN_PROGRESS)->value('id');

        Ticket::factory()->create([
            'status_id' => $openStatusId,
            'opened_at' => now()->subMinutes(20),
            'user_id' => $technician->id,
        ]);

        Ticket::factory()->create([
            'status_id' => $inProgressStatusId,
            'opened_at' => now()->subMinutes(30),
            'assigned_to' => $technician->id,
            'user_id' => $technician->id,
        ]);

        Ticket::factory()->create([
            'status_id' => $closedStatusId,
            'opened_at' => now()->subDays(1),
            'closed_at' => now()->subHours(2),
            'user_id' => $technician->id,
        ]);

        $this->withHeader('X-Auth-Token', $technician->api_token)
            ->getJson('/analytics')
            ->assertOk()
            ->assertJsonStructure([
                'open_tickets',
                'closed_tickets',
                'ticket_status_breakdown',
                'recent_activity',
                'top_equipments',
                'top_rooms',
                'top_technicians',
            ]);
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
            ->getJson('/analytics/export/csv')
            ->assertStatus(403);
    }

    public function test_admin_can_export_analytics_reports(): void
    {
        $adminProfile = UserProfile::where('name', User::ROLE_ADMIN)->firstOrFail();
        $admin = User::factory()->create([
            'profile_id' => $adminProfile->id,
            'api_token' => Str::random(60),
        ]);

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->getJson('/analytics/export/csv')
            ->assertOk();

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->getJson('/analytics/export/excel')
            ->assertOk();
    }
}
