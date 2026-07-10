<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminManagementTest extends TestCase
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

    public function test_admin_can_manage_users_rooms_and_equipment(): void
    {
        $adminProfile = UserProfile::where('name', User::ROLE_ADMIN)->firstOrFail();
        $userProfile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();

        $admin = User::factory()->create([
            'profile_id' => $adminProfile->id,
            'api_token' => Str::random(60),
        ]);

        $target = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);

        $users = $this->withHeader('X-Auth-Token', $admin->api_token)->getJson('/admin/users');
        $users->assertOk();
        $users->assertJsonStructure(['users']);

        $inactive = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->patchJson('/admin/users/' . $target->id . '/inactive');
        $inactive->assertOk();
        $this->assertFalse($target->fresh()->active);

        $room = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->postJson('/admin/rooms', ['name' => 'A1', 'location' => 'Floor 1']);
        $room->assertCreated();
        $roomId = $room->json('room.id');

        $roomUpdate = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->patchJson('/admin/rooms/' . $roomId, ['location' => 'Floor 2']);
        $roomUpdate->assertOk();
        $this->assertSame('Floor 2', Room::findOrFail($roomId)->location);

        $equipment = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->postJson('/admin/equipment', [
                'name' => 'Projector',
                'serial' => 'PRJ-001',
                'room_id' => $roomId,
            ]);
        $equipment->assertCreated();
        $equipmentId = $equipment->json('equipment.id');

        $equipmentUpdate = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->patchJson('/admin/equipment/' . $equipmentId, ['active' => false]);
        $equipmentUpdate->assertOk();
        $this->assertFalse(Equipment::findOrFail($equipmentId)->active);

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->patchJson('/admin/rooms/' . $roomId . '/inactive')
            ->assertOk();

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->deleteJson('/admin/equipment/' . $equipmentId)
            ->assertOk();

        $this->assertDatabaseMissing('equipments', ['id' => $equipmentId]);
    }

    public function test_admin_can_approve_budget(): void
    {
        $adminProfile = UserProfile::where('name', User::ROLE_ADMIN)->firstOrFail();
        $userProfile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();

        $admin = User::factory()->create([
            'profile_id' => $adminProfile->id,
            'api_token' => Str::random(60),
        ]);
        $creator = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);

        $ticket = Ticket::create([
            'user_id' => $creator->id,
            'title' => 'Budget flow',
            'description' => 'Needs approval',
            'status_id' => Ticket::getStatusIdByName(Ticket::STATUS_IN_PROGRESS),
            'opened_at' => now(),
            'cost' => 250,
            'budget_requested' => true,
            'budget_status' => Ticket::BUDGET_PENDING,
            'budget_amount' => 250,
        ]);

        $response = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->patchJson('/admin/tickets/' . $ticket->id . '/approve-budget');

        $response->assertOk();
        $this->assertSame(Ticket::BUDGET_APPROVED, $ticket->fresh()->budget_status);
    }

    public function test_admin_inventory_routes_require_admin_role_and_reject_invalid_serials(): void
    {
        $adminProfile = UserProfile::where('name', User::ROLE_ADMIN)->firstOrFail();
        $userProfile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();
        $technicianProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->firstOrFail();

        $commonUser = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);
        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);
        $admin = User::factory()->create([
            'profile_id' => $adminProfile->id,
            'api_token' => Str::random(60),
        ]);

        foreach ([$commonUser, $technician] as $blockedUser) {
            $this->withHeader('X-Auth-Token', $blockedUser->api_token)
                ->getJson('/admin/equipment')
                ->assertStatus(403);

            $this->withHeader('X-Auth-Token', $blockedUser->api_token)
                ->getJson('/admin/rooms')
                ->assertStatus(403);
        }

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->getJson('/admin/equipment')
            ->assertOk();

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->getJson('/admin/rooms')
            ->assertOk();

        $this->withHeader('X-Auth-Token', $admin->api_token)
            ->postJson('/admin/equipment', [
                'name' => 'Projector base',
                'serial' => 'PRJ-001',
                'room_id' => null,
            ])
            ->assertCreated();

        $duplicateResponse = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->postJson('/admin/equipment', [
                'name' => 'Duplicated projector',
                'serial' => 'PRJ-001',
                'room_id' => null,
            ]);

        $duplicateResponse->assertStatus(422);
        $duplicateResponse->assertJsonStructure(['errors' => ['serial']]);
    }

    public function test_admin_can_schedule_preventive_maintenance(): void
    {
        $adminProfile = UserProfile::where('name', User::ROLE_ADMIN)->firstOrFail();
        $technicianProfile = UserProfile::where('name', User::ROLE_TECHNICIAN)->firstOrFail();

        $admin = User::factory()->create([
            'profile_id' => $adminProfile->id,
            'api_token' => Str::random(60),
        ]);
        $technician = User::factory()->create([
            'profile_id' => $technicianProfile->id,
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeader('X-Auth-Token', $admin->api_token)
            ->postJson('/admin/preventive', [
                'title' => 'Manutenção preventiva de ar-condicionado',
                'description' => 'Verificar filtros e gás.',
                'scheduled_at' => now()->addWeek()->toDateTimeString(),
                'technician_id' => $technician->id,
            ]);

        $response->assertCreated();
        $ticketId = $response->json('ticket.id');
        $this->assertNotNull($ticketId);

        $ticket = Ticket::findOrFail($ticketId);
        $this->assertTrue($ticket->scheduled);
        $this->assertEquals($technician->id, $ticket->assigned_to);
        $this->assertNotNull($ticket->scheduled_at);
    }
}
