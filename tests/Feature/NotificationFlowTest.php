<?php

namespace Tests\Feature;

use App\Mail\TestMail;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserProfile::create(['name' => User::ROLE_USER]);
    }

    public function test_user_can_list_notifications_mark_as_read_and_send_test_email(): void
    {
        Mail::fake();

        $profile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();
        $user = User::factory()->create([
            'profile_id' => $profile->id,
            'api_token' => Str::random(60),
            'email' => 'teacher@example.com',
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Avaria atualizada',
            'message' => 'O ticket foi atualizado.',
            'type' => 'ticket',
            'is_read' => false,
            'link' => '/ui/tickets/1',
        ]);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonStructure(['notifications']);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->patchJson('/notifications/' . $notification->id)
            ->assertOk();

        $this->assertTrue($notification->fresh()->is_read);

        $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/notifications/test-email')
            ->assertOk();

        Mail::assertSent(TestMail::class);
    }

    public function test_guest_is_redirected_from_notifications_endpoints(): void
    {
        $this->get('/notifications')->assertRedirect('/ui/login');
        $this->patch('/notifications/1')->assertRedirect('/ui/login');
        $this->post('/notifications/test-email')->assertRedirect('/ui/login');
    }

    public function test_invalid_token_cannot_access_notifications_endpoints(): void
    {
        $this->withHeader('X-Auth-Token', 'invalid-token')
            ->getJson('/notifications')
            ->assertStatus(401);

        $this->withHeader('X-Auth-Token', 'invalid-token')
            ->postJson('/notifications/test-email')
            ->assertStatus(401);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $profile = UserProfile::where('name', User::ROLE_USER)->firstOrFail();
        $owner = User::factory()->create([
            'profile_id' => $profile->id,
            'api_token' => Str::random(60),
        ]);
        $otherUser = User::factory()->create([
            'profile_id' => $profile->id,
            'api_token' => Str::random(60),
        ]);

        $notification = Notification::create([
            'user_id' => $owner->id,
            'title' => 'Private update',
            'message' => 'This belongs to the owner.',
            'type' => 'ticket',
            'is_read' => false,
            'link' => '/ui/tickets/2',
        ]);

        $response = $this->withHeader('X-Auth-Token', $otherUser->api_token)
            ->patchJson('/notifications/' . $notification->id);

        $response->assertStatus(404);
        $this->assertFalse($notification->fresh()->is_read);
    }
}
