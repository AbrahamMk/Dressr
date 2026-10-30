<?php

namespace Database\Factories;

use App\Models\Measurement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Measurement>
 */
class MeasurementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'measured_on' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'height_cm' => fake()->randomFloat(1, 150, 200),
            'weight_kg' => fake()->randomFloat(1, 50, 120),
            'neck_cm' => fake()->randomFloat(1, 30, 45),
            'shoulder_width_cm' => fake()->randomFloat(1, 35, 55),
            'chest_cm' => fake()->randomFloat(1, 80, 120),
            'waist_cm' => fake()->randomFloat(1, 60, 110),
            'hips_cm' => fake()->randomFloat(1, 85, 125),
            'inseam_cm' => fake()->randomFloat(1, 65, 90),
            'arm_length_cm' => fake()->randomFloat(1, 55, 70),
            'thigh_cm' => fake()->randomFloat(1, 45, 70),
        ];
    }
}
