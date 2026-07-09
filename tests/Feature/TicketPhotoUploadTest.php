<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar perfis necessários para os testes
        \App\Models\UserProfile::create(['name' => User::ROLE_USER]);
        \App\Models\UserProfile::create(['name' => User::ROLE_TECHNICIAN]);
        \App\Models\UserProfile::create(['name' => User::ROLE_ADMIN]);

        // Criar estados de ticket
        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    private function createUserWithToken(string $profileName): User
    {
        $profile = \App\Models\UserProfile::where('name', $profileName)->firstOrFail();

        return User::factory()->create([
            'profile_id' => $profile->id,
            'api_token' => Str::random(60),
        ]);
    }

    public function test_ticket_photo_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithToken(User::ROLE_USER);

        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Avaria teste',
            'description' => 'Descrição da avaria',
            'status_id' => $openStatusId,
            'opened_at' => now(),
        ]);

        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/tickets/' . $ticket->id . '/photos', [
                'photo' => UploadedFile::fake()->create('damage.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('attachment.file_name', 'damage.jpg');
        $this->assertStringStartsWith('ticket_photos/', $response->json('attachment.path'));
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_ticket_photo_upload_requires_photo_field(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithToken(User::ROLE_USER);
        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Avaria teste',
            'description' => 'Descrição da avaria',
            'status_id' => $openStatusId,
            'opened_at' => now(),
        ]);

        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/tickets/' . $ticket->id . '/photos', [
                // sem photo
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['photo']]);
    }

    public function test_ticket_photo_upload_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithToken(User::ROLE_USER);
        $openStatusId = Ticket::getStatusIdByName(Ticket::STATUS_OPEN);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Avaria teste',
            'description' => 'Descrição da avaria',
            'status_id' => $openStatusId,
            'opened_at' => now(),
        ]);

        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/tickets/' . $ticket->id . '/photos', [
                'photo' => UploadedFile::fake()->create('damage.txt', 10, 'text/plain'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['photo']]);
    }

    public function test_ticket_photo_upload_rejects_when_ticket_does_not_exist(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithToken(User::ROLE_USER);

        $response = $this->withHeader('X-Auth-Token', $user->api_token)
            ->postJson('/tickets/999999/photos', [
                'photo' => UploadedFile::fake()->create('damage.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Ticket não encontrado']);
    }
}

