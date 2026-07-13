<?php

namespace Database\Factories;

use App\Models\EquipmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentCategory>
 */
class EquipmentCategoryFactory extends Factory
{
    protected $model = EquipmentCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'active' => true,
        ];
    }
}

