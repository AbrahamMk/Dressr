<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WearLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WearLog>
 */
class WearLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'outfit_id' => null,
            'worn_on' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
        ];
    }
}
