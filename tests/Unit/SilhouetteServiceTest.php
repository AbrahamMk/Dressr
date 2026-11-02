<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Measurement;
use App\Services\Silhouette\SilhouetteService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SilhouetteServiceTest extends TestCase
{
    private SilhouetteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SilhouetteService;
    }

    public function test_null_measurement_returns_empty_result(): void
    {
        $result = $this->service->classify(null);

        $this->assertNull($result->shape);
        $this->assertNull($result->label);
    }

    #[DataProvider('shapeExamples')]
    public function test_every_shape_label(array $measures, string $expected): void
    {
        $result = $this->service->classify($this->measurement($measures));

        $this->assertSame($expected, $result->shape);
        $this->assertNotEmpty($result->label);
        $this->assertNotEmpty($result->explanation);
        $this->assertNotEmpty($result->favored);
    }

    /**
     * @return array<string, array{0: array<string, float>, 1: string}>
     */
    public static function shapeExamples(): array
    {
        return [
            'hourglass' => [
                ['chest_cm' => 100, 'waist_cm' => 70, 'hips_cm' => 100],
                'hourglass',
            ],
            'pear' => [
                ['chest_cm' => 90, 'waist_cm' => 75, 'hips_cm' => 110],
                'pear',
            ],
            'inverted_triangle' => [
                ['chest_cm' => 110, 'waist_cm' => 85, 'hips_cm' => 95],
                'inverted_triangle',
            ],
            'apple' => [
                // Full midsection, hips narrower — outside the rectangle band.
                ['chest_cm' => 100, 'waist_cm' => 105, 'hips_cm' => 90],
                'apple',
            ],
            'rectangle' => [
                ['chest_cm' => 94, 'waist_cm' => 93, 'hips_cm' => 95],
                'rectangle',
            ],
        ];
    }

    public function test_evaluation_order_prefers_hourglass_before_rectangle(): void
    {
        // Balanced chest/hips with defined waist is hourglass, not rectangle.
        $result = $this->service->classify($this->measurement([
            'chest_cm' => 96,
            'waist_cm' => 70,
            'hips_cm' => 98,
        ]));

        $this->assertSame('hourglass', $result->shape);
    }

    public function test_evaluation_order_prefers_pear_before_apple_like_ratios(): void
    {
        $result = $this->service->classify($this->measurement([
            'chest_cm' => 88,
            'waist_cm' => 80,
            'hips_cm' => 108,
        ]));

        $this->assertSame('pear', $result->shape);
    }

    public function test_score_item_rewards_favored_and_penalizes_avoided(): void
    {
        $shape = $this->service->classify($this->measurement([
            'chest_cm' => 100,
            'waist_cm' => 70,
            'hips_cm' => 100,
        ]));

        $this->assertSame('hourglass', $shape->shape);

        $favored = new Item(['cut_tags' => ['fitted', 'wrap']]);
        $avoided = new Item(['cut_tags' => ['oversized']]);
        $neutral = new Item(['cut_tags' => []]);

        $this->assertGreaterThan(0.5, $this->service->scoreItem($shape, $favored));
        $this->assertLessThan(0.5, $this->service->scoreItem($shape, $avoided));
        $this->assertSame(0.5, $this->service->scoreItem($shape, $neutral));
    }

    public function test_score_item_is_clamped(): void
    {
        $shape = $this->service->classify($this->measurement([
            'chest_cm' => 100,
            'waist_cm' => 70,
            'hips_cm' => 100,
        ]));

        $many = new Item([
            'cut_tags' => ['fitted', 'high_waist', 'wrap', 'a_line', 'belted', 'extra'],
        ]);

        $this->assertSame(1.0, $this->service->scoreItem($shape, $many));
    }

    public function test_cut_preferences_expose_favored_and_avoided(): void
    {
        $prefs = $this->service->cutPreferences('pear');

        $this->assertContains('a_line', $prefs['favored']);
        $this->assertContains('slim_leg', $prefs['avoided']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function measurement(array $overrides = []): Measurement
    {
        return new Measurement(array_merge([
            'height_cm' => 170,
            'chest_cm' => 95,
            'waist_cm' => 75,
            'hips_cm' => 98,
        ], $overrides));
    }
}
