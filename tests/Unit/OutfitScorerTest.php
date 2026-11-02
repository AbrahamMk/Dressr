<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Measurement;
use App\Services\Fit\FitService;
use App\Services\Outfit\ColorHarmony;
use App\Services\Outfit\OutfitScorer;
use App\Services\Outfit\VarietyService;
use App\Services\Silhouette\SilhouetteResult;
use App\Services\Silhouette\SilhouetteService;
use Tests\TestCase;

class OutfitScorerTest extends TestCase
{
    private OutfitScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scorer = new OutfitScorer(
            new ColorHarmony,
            new FitService,
            new SilhouetteService,
            new VarietyService,
        );
    }

    public function test_variety_is_full_without_wear_history(): void
    {
        $items = $this->sampleItems();
        $score = $this->scorer->score(
            $items,
            ['occasion' => 'casual', 'season' => 'summer', 'temperature' => 'mild'],
            null,
            new SilhouetteResult(null, null, null),
        );

        $this->assertSame(100.0, $score->factors['variety']);
    }

    public function test_variety_drops_for_recently_worn_items_and_exact_repeats(): void
    {
        $items = $this->sampleItems();
        foreach ($items as $index => $item) {
            $item->id = $index + 1;
        }

        $history = [
            'item_ids' => [1, 2],
            'combo_keys' => ['1-2-3'],
        ];

        $score = $this->scorer->score(
            $items,
            ['occasion' => 'casual', 'season' => 'summer', 'temperature' => 'mild'],
            null,
            new SilhouetteResult(null, null, null),
            $history,
        );

        $this->assertLessThan(100.0, $score->factors['variety']);
    }

    public function test_formality_matches_occasion_band(): void
    {
        $casual = [
            new Item(['primary_color_hex' => '#111827', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['summer'], 'category' => 'top', 'cut_tags' => []]),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['summer'], 'category' => 'bottom', 'cut_tags' => []]),
            new Item(['primary_color_hex' => '#1e3a8a', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['summer'], 'category' => 'shoes', 'cut_tags' => []]),
        ];

        $formal = [
            new Item(['primary_color_hex' => '#111827', 'pattern' => 'solid', 'formality' => 5, 'seasons' => ['summer'], 'category' => 'top', 'cut_tags' => []]),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'solid', 'formality' => 5, 'seasons' => ['summer'], 'category' => 'bottom', 'cut_tags' => []]),
            new Item(['primary_color_hex' => '#1e3a8a', 'pattern' => 'solid', 'formality' => 5, 'seasons' => ['summer'], 'category' => 'shoes', 'cut_tags' => []]),
        ];

        $shape = new SilhouetteResult(null, null, null);
        $context = ['occasion' => 'casual', 'season' => 'summer', 'temperature' => 'mild'];

        $casualScore = $this->scorer->score($casual, $context, null, $shape);
        $formalScore = $this->scorer->score($formal, $context, null, $shape);

        $this->assertGreaterThan($formalScore->factors['formality'], $casualScore->factors['formality']);
    }

    public function test_season_mismatch_lowers_season_factor(): void
    {
        $items = [
            new Item(['primary_color_hex' => '#111827', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['winter'], 'category' => 'top', 'cut_tags' => []]),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['winter'], 'category' => 'bottom', 'cut_tags' => []]),
            new Item(['primary_color_hex' => '#1e3a8a', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['winter'], 'category' => 'shoes', 'cut_tags' => []]),
        ];

        $score = $this->scorer->score(
            $items,
            ['occasion' => 'casual', 'season' => 'summer', 'temperature' => 'mild'],
            null,
            new SilhouetteResult(null, null, null),
        );

        $this->assertSame(0.0, $score->factors['season']);
    }

    public function test_total_is_weighted_between_zero_and_one_hundred(): void
    {
        $score = $this->scorer->score(
            $this->sampleItems(),
            ['occasion' => 'casual', 'season' => 'summer', 'temperature' => 'mild'],
            new Measurement(['chest_cm' => 100, 'waist_cm' => 80, 'hips_cm' => 100, 'height_cm' => 175]),
            (new SilhouetteService)->classify(new Measurement([
                'chest_cm' => 100,
                'waist_cm' => 70,
                'hips_cm' => 100,
                'height_cm' => 175,
            ])),
        );

        $this->assertGreaterThan(0, $score->total);
        $this->assertLessThanOrEqual(100, $score->total);
    }

    /**
     * @return list<Item>
     */
    private function sampleItems(): array
    {
        return [
            new Item(['primary_color_hex' => '#1e3a8a', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['summer'], 'category' => 'top', 'cut_tags' => ['fitted'], 'stretch' => 'none']),
            new Item(['primary_color_hex' => '#111827', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['summer'], 'category' => 'bottom', 'cut_tags' => ['straight'], 'stretch' => 'none']),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'solid', 'formality' => 2, 'seasons' => ['summer'], 'category' => 'shoes', 'cut_tags' => [], 'stretch' => 'none']),
        ];
    }
}
