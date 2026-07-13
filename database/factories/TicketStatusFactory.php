<?php

namespace Database\Factories;

use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatus>
 */
class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'aberta',
                'em curso',
                'fechada',
                'cancelada',
                'pendente orçamento',
                'recusada',
                'aguarda peças',
                'em revisão',
                'sem rede',
            ]),
            'description' => $this->faker->sentence(12),
            'type_id' => null,
        ];
    }
}

