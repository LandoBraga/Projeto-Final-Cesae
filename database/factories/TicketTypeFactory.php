<?php

namespace Database\Factories;

use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Hardware',
                'Software',
                'Rede / Internet',
            ]),
            'description' => $this->faker->sentence(12),
        ];
    }
}

