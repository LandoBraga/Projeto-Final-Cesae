<?php

namespace Database\Factories;

use App\Models\TicketAttachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'file_name' => $this->faker->fileName(),
            'path' => 'ticket_photos/' . $this->faker->fileName(),
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1024, 1024 * 500),
        ];
    }
}

