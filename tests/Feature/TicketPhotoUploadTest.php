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
        
        // Criar estados de ticket
        $this->artisan('db:seed', ['--class' => 'TicketLookupSeeder', '--force' => true]);
    }

    public function test_ticket_photo_can_be_uploaded(): void
    {
        Storage::fake('public');

        $userProfile = \App\Models\UserProfile::where('name', User::ROLE_USER)->first();
        
        $user = User::factory()->create([
            'profile_id' => $userProfile->id,
            'api_token' => Str::random(60),
        ]);

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
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }
}
