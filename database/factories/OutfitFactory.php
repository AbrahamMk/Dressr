<?php

namespace Database\Factories;

use App\Enums\OutfitSource;
use App\Enums\Season;
use App\Models\Outfit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outfit>
 */
class OutfitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'occasion' => fake()->randomElement(array_keys(config('stylist.scoring.occasions'))),
            'season' => fake()->randomElement(Season::values()),
            'score' => fake()->randomFloat(2, 40, 95),
            'source' => OutfitSource::Rules->value,
            'reasoning' => null,
            'is_favorite' => false,
        ];
    }

    public function favorite(): static
    {
        return $this->state(fn () => ['is_favorite' => true]);
    }
}
