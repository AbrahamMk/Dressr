<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Measurement;
use App\Services\Fit\FitService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FitServiceTest extends TestCase
{
    private FitService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FitService;
    }

    public function test_missing_measurement_is_unknown(): void
    {
        $item = new Item(['category' => 'top', 'stretch' => 'none']);

        $result = $this->service->evaluate(null, $item);

        $this->assertSame('unknown', $result->label);
        $this->assertNull($result->score);
    }

    public function test_shoes_and_accessories_are_unknown(): void
    {
        $measurement = $this->measurement();

        foreach (['shoes', 'accessory'] as $category) {
            $item = new Item(['category' => $category, 'stretch' => 'none']);
            $result = $this->service->evaluate($measurement, $item);

            $this->assertSame('unknown', $result->label, $category);
            $this->assertNull($result->score, $category);
        }
    }

    public function test_missing_garment_measurements_are_unknown(): void
    {
        $measurement = $this->measurement();
        $item = new Item([
            'category' => 'top',
            'stretch' => 'none',
            'g_chest_width' => null,
            'g_shoulder_width' => null,
        ]);

        $result = $this->service->evaluate($measurement, $item);

        $this->assertSame('unknown', $result->label);
        $this->assertNull($result->score);
    }

    /**
     * Chest ease = (g_chest_width * 2) - chest_cm.
     * With chest 100: good [4,12] → flat width 52..56; acceptable [2,18] → 51..59.
     */
    #[DataProvider('chestEaseBoundaries')]
    public function test_chest_ease_boundaries(float $flatWidth, string $expectedLabel): void
    {
        $measurement = $this->measurement(['chest_cm' => 100, 'shoulder_width_cm' => 40]);
        $item = new Item([
            'category' => 'top',
            'stretch' => 'none',
            'g_chest_width' => $flatWidth,
            'g_shoulder_width' => 40, // ease 0 → good for shoulder
        ]);

        $result = $this->service->evaluate($measurement, $item);

        $this->assertSame($expectedLabel, $result->label);
        $chest = collect($result->eases)->firstWhere('pair', 'chest');
        $this->assertSame($expectedLabel, $chest['label']);
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function chestEaseBoundaries(): array
    {
        return [
            'good lower bound (ease 4)' => [52.0, 'good_fit'],
            'good upper bound (ease 12)' => [56.0, 'good_fit'],
            'acceptable lower (ease 2)' => [51.0, 'acceptable'],
            'acceptable upper (ease 18)' => [59.0, 'acceptable'],
            'tight (ease 1)' => [50.5, 'tight'],
            'loose (ease 20)' => [60.0, 'loose'],
        ];
    }

    public function test_stretch_makes_tight_acceptable(): void
    {
        $measurement = $this->measurement(['chest_cm' => 100, 'shoulder_width_cm' => 40]);

        // ease 1 without stretch → tight; with some (+2) lower bound becomes 0 → acceptable/good
        $itemNone = new Item([
            'category' => 'top',
            'stretch' => 'none',
            'g_chest_width' => 50.5,
            'g_shoulder_width' => 40,
        ]);
        $itemSome = new Item([
            'category' => 'top',
            'stretch' => 'some',
            'g_chest_width' => 50.5,
            'g_shoulder_width' => 40,
        ]);

        $this->assertSame('tight', $this->service->evaluate($measurement, $itemNone)->label);
        $this->assertSame('acceptable', $this->service->evaluate($measurement, $itemSome)->label);
    }

    public function test_high_stretch_allows_even_closer_fit(): void
    {
        $measurement = $this->measurement(['chest_cm' => 100, 'shoulder_width_cm' => 40]);

        // ease -1: tight with none/some; high stretch lowers bound by 4 → acceptable
        $item = new Item([
            'category' => 'top',
            'stretch' => 'high',
            'g_chest_width' => 49.5,
            'g_shoulder_width' => 40,
        ]);

        $this->assertSame('acceptable', $this->service->evaluate($measurement, $item)->label);
    }

    public function test_outerwear_gets_extra_ease(): void
    {
        $measurement = $this->measurement(['chest_cm' => 100, 'shoulder_width_cm' => 40]);

        // ease 16: loose for a top (acceptable max 18... wait 16 is acceptable for top)
        // Use ease 20 which is loose for top; outerwear +4 widens acceptable to [6,22] wait:
        // acceptable [2,18] + outer 4 → [6,22], stretch 0.
        // Actually adjustRange: min = 2 - 0 + 4 = 6, max = 18 + 4 = 22.
        // So ease 20 is acceptable for outerwear, loose for top.
        $attrs = [
            'stretch' => 'none',
            'g_chest_width' => 60.0, // ease 20
            'g_shoulder_width' => 40,
        ];

        $top = new Item(array_merge($attrs, ['category' => 'top']));
        $outer = new Item(array_merge($attrs, ['category' => 'outerwear']));

        $this->assertSame('loose', $this->service->evaluate($measurement, $top)->label);
        $this->assertSame('acceptable', $this->service->evaluate($measurement, $outer)->label);
    }

    public function test_dress_uses_chest_waist_hips(): void
    {
        $measurement = $this->measurement([
            'chest_cm' => 100,
            'waist_cm' => 80,
            'hips_cm' => 100,
        ]);

        $item = new Item([
            'category' => 'dress',
            'stretch' => 'none',
            'g_chest_width' => 54, // ease 8 good
            'g_waist_width' => 42, // ease 4 good
            'g_hip_width' => 54, // ease 8 good
        ]);

        $result = $this->service->evaluate($measurement, $item);

        $this->assertSame('good_fit', $result->label);
        $this->assertSame(
            ['chest', 'waist', 'hips'],
            array_column($result->eases, 'pair')
        );
    }

    public function test_inseam_is_checked_for_trousers(): void
    {
        $measurement = $this->measurement([
            'waist_cm' => 80,
            'hips_cm' => 100,
            'inseam_cm' => 80,
        ]);

        $item = new Item([
            'category' => 'bottom',
            'subcategory' => 'jeans',
            'stretch' => 'none',
            'cut_tags' => [],
            'g_waist_width' => 42, // ease 4
            'g_hip_width' => 54, // ease 8
            'g_inseam' => 90, // ease 10 → loose
        ]);

        $result = $this->service->evaluate($measurement, $item);

        $this->assertSame('loose', $result->label);
        $this->assertContains('inseam', array_column($result->eases, 'pair'));
    }

    public function test_inseam_skipped_for_cropped_and_shorts(): void
    {
        $measurement = $this->measurement([
            'waist_cm' => 80,
            'hips_cm' => 100,
            'inseam_cm' => 80,
        ]);

        $base = [
            'category' => 'bottom',
            'stretch' => 'none',
            'g_waist_width' => 42,
            'g_hip_width' => 54,
            'g_inseam' => 40, // would be very tight if evaluated
        ];

        $cropped = new Item(array_merge($base, [
            'subcategory' => 'trousers',
            'cut_tags' => ['cropped'],
        ]));
        $shorts = new Item(array_merge($base, [
            'subcategory' => 'shorts',
            'cut_tags' => [],
        ]));

        foreach ([$cropped, $shorts] as $item) {
            $result = $this->service->evaluate($measurement, $item);
            $this->assertSame('good_fit', $result->label);
            $this->assertNotContains('inseam', array_column($result->eases, 'pair'));
        }
    }

    public function test_overall_label_prefers_tight_over_loose(): void
    {
        $measurement = $this->measurement([
            'chest_cm' => 100,
            'waist_cm' => 80,
            'hips_cm' => 100,
        ]);

        $item = new Item([
            'category' => 'dress',
            'stretch' => 'none',
            'g_chest_width' => 48, // ease -4 tight
            'g_waist_width' => 50, // ease 20 loose
            'g_hip_width' => 54, // good
        ]);

        $this->assertSame('tight', $this->service->evaluate($measurement, $item)->label);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function measurement(array $overrides = []): Measurement
    {
        return new Measurement(array_merge([
            'height_cm' => 175,
            'chest_cm' => 100,
            'waist_cm' => 80,
            'hips_cm' => 100,
            'shoulder_width_cm' => 40,
            'inseam_cm' => 80,
        ], $overrides));
    }
}
