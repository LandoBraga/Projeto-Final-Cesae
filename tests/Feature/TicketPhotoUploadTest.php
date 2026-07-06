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

    public function test_ticket_photo_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'api_token' => Str::random(60),
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Avaria teste',
            'description' => 'Descrição da avaria',
            'status' => Ticket::STATUS_OPEN,
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
