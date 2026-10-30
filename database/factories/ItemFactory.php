<?php

namespace Database\Factories;

use App\Enums\ItemCategory;
use App\Enums\ItemFitType;
use App\Enums\ItemPattern;
use App\Enums\ItemStretch;
use App\Enums\Season;
use App\Models\Item;
use App\Models\User;
use App\Support\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hex = fake()->randomElement([
            '#111827', '#1e3a8a', '#b91c1c', '#f59e0b',
            '#166534', '#7c3aed', '#e5e7eb', '#8b5a2b',
        ]);

        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'category' => fake()->randomElement(ItemCategory::values()),
            'subcategory' => fake()->randomElement(['tshirt', 'shirt', 'jeans', 'chinos', 'jacket', 'dress', 'sneakers']),
            'primary_color_hex' => $hex,
            'secondary_color_hex' => null,
            'color_family' => Color::family($hex),
            'pattern' => ItemPattern::Solid->value,
            'material' => fake()->randomElement(['cotton', 'linen', 'wool', 'denim']),
            'seasons' => [fake()->randomElement(Season::values())],
            'formality' => fake()->numberBetween(1, 5),
            'fit_type' => fake()->randomElement(ItemFitType::values()),
            'stretch' => fake()->randomElement(ItemStretch::values()),
            'cut_tags' => [],
            'size_label' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'is_active' => true,
        ];
    }
}
