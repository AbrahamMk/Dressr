<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\User;
use App\Services\Fit\FitService;
use App\Services\Outfit\ColorHarmony;
use App\Services\Outfit\OutfitGenerator;
use App\Services\Outfit\OutfitScorer;
use App\Services\Outfit\VarietyService;
use App\Services\Silhouette\SilhouetteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutfitGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private OutfitGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $variety = new VarietyService;

        $this->generator = new OutfitGenerator(
            new OutfitScorer(new ColorHarmony, new FitService, new SilhouetteService, $variety),
            new SilhouetteService,
            $variety,
        );
    }

    public function test_hard_filter_drops_wrong_season_and_extreme_formality(): void
    {
        $items = collect([
            new Item(['category' => 'top', 'seasons' => ['summer'], 'formality' => 2, 'is_active' => true]),
            new Item(['category' => 'top', 'seasons' => ['winter'], 'formality' => 2, 'is_active' => true]),
            new Item(['category' => 'top', 'seasons' => ['summer'], 'formality' => 5, 'is_active' => true]),
        ]);

        $filtered = $this->generator->hardFilter($items, [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'mild',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame(2, $filtered->first()->formality);
    }

    public function test_not_enough_items_returns_clear_message(): void
    {
        $user = User::factory()->create();
        Item::factory()->for($user)->create([
            'category' => 'top',
            'seasons' => ['summer'],
            'formality' => 2,
            'is_active' => true,
        ]);

        $result = $this->generator->generate($user, [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'mild',
            'count' => 5,
        ]);

        $this->assertSame([], $result['outfits']);
        $this->assertNotNull($result['message']);
        $this->assertStringContainsString('Not enough items', $result['message']);
    }

    public function test_generates_outfits_for_casual_summer(): void
    {
        $user = User::factory()->create();
        $this->seedWardrobe($user);

        $result = $this->generator->generate($user, [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'hot',
            'count' => 5,
        ]);

        $this->assertNull($result['message']);
        $this->assertNotEmpty($result['outfits']);
        $this->assertLessThanOrEqual(5, count($result['outfits']));

        foreach ($result['outfits'] as $outfit) {
            $slots = array_column($outfit['items'], 'slot');
            $this->assertContains('shoes', $slots);
            $this->assertTrue(
                in_array('dress', $slots, true)
                || (in_array('top', $slots, true) && in_array('bottom', $slots, true))
            );
            $this->assertNotContains('outer', $slots); // hot excludes outerwear
            $this->assertArrayHasKey('factors', $outfit);
            $this->assertArrayHasKey('score', $outfit);
        }
    }

    public function test_cold_temperature_requires_outerwear_when_available(): void
    {
        $user = User::factory()->create();
        $this->seedWardrobe($user, withOuter: true);

        $result = $this->generator->generate($user, [
            'occasion' => 'casual',
            'season' => 'winter',
            'temperature' => 'cold',
            'count' => 3,
        ]);

        $this->assertNotEmpty($result['outfits']);

        foreach ($result['outfits'] as $outfit) {
            $slots = array_column($outfit['items'], 'slot');
            $this->assertContains('outer', $slots);
        }
    }

    public function test_results_are_deduplicated(): void
    {
        $user = User::factory()->create();
        $this->seedWardrobe($user);

        $result = $this->generator->generate($user, [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'mild',
            'count' => 5,
        ]);

        $keys = array_map(
            fn (array $outfit) => implode('-', $outfit['item_ids']),
            $result['outfits']
        );

        $this->assertSame($keys, array_unique($keys));
    }

    private function seedWardrobe(User $user, bool $withOuter = false): void
    {
        $season = $withOuter ? 'winter' : 'summer';

        Item::factory()->for($user)->create([
            'name' => 'Tee',
            'category' => 'top',
            'seasons' => [$season],
            'formality' => 2,
            'primary_color_hex' => '#1e3a8a',
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'name' => 'Shirt',
            'category' => 'top',
            'seasons' => [$season],
            'formality' => 3,
            'primary_color_hex' => '#e5e7eb',
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'name' => 'Chinos',
            'category' => 'bottom',
            'seasons' => [$season],
            'formality' => 2,
            'primary_color_hex' => '#78350f',
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'name' => 'Jeans',
            'category' => 'bottom',
            'seasons' => [$season],
            'formality' => 2,
            'primary_color_hex' => '#1e3a8a',
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'name' => 'Sneakers',
            'category' => 'shoes',
            'seasons' => [$season],
            'formality' => 2,
            'primary_color_hex' => '#111827',
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'name' => 'Sundress',
            'category' => 'dress',
            'seasons' => [$season],
            'formality' => 2,
            'primary_color_hex' => '#f43f5e',
            'is_active' => true,
        ]);

        if ($withOuter) {
            Item::factory()->for($user)->create([
                'name' => 'Coat',
                'category' => 'outerwear',
                'seasons' => [$season],
                'formality' => 3,
                'primary_color_hex' => '#374151',
                'is_active' => true,
            ]);
        }
    }
}
