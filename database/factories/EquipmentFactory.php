<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' ' . $this->faker->word(),
            'serial' => strtoupper($this->faker->unique()->bothify('EQ-###-????')),
            'room_id' => Room::factory(),
            'category_id' => EquipmentCategory::factory(),
            'active' => true,
        ];
    }
}

