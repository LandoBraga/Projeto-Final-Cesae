<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'assigned_to' => null,
            'room_id' => null,
            'equipment_id' => null,
            'status_id' => TicketStatus::query()->inRandomOrder()->value('id'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement([
                Ticket::PRIORITY_LOW,
                Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH,
            ]),
            'opened_at' => now()->subHours(2),
            'in_progress_at' => null,
            'closed_at' => null,
            'reopened_at' => null,
            'scheduled_at' => null,
            'scheduled_end' => null,
            'scheduled' => false,
            'minutes_spent' => fake()->numberBetween(15, 240),
            'cost' => fake()->randomFloat(2, 0, 250),
            'budget_requested' => false,
            'budget_status' => null,
            'budget_amount' => null,
            'budget_approved_by' => null,
        ];
    }
}
