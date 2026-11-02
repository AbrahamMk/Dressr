<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Services\Outfit\ColorHarmony;
use Tests\TestCase;

class ColorHarmonyTest extends TestCase
{
    private ColorHarmony $harmony;

    protected function setUp(): void
    {
        parent::setUp();

        $this->harmony = new ColorHarmony;
    }

    public function test_hex_to_hsl_black_and_red(): void
    {
        [$h, $s, $l] = $this->harmony->toHsl('#000000');
        $this->assertEqualsWithDelta(0.0, $h, 0.0001);
        $this->assertEqualsWithDelta(0.0, $s, 0.0001);
        $this->assertEqualsWithDelta(0.0, $l, 0.0001);

        [$h, $s, $l] = $this->harmony->toHsl('#ff0000');
        $this->assertEqualsWithDelta(0.0, $h, 0.1);
        $this->assertEqualsWithDelta(1.0, $s, 0.01);
        $this->assertEqualsWithDelta(0.5, $l, 0.01);
    }

    public function test_neutrals_are_detected(): void
    {
        $this->assertTrue($this->harmony->isNeutral($this->harmony->toHsl('#111111')));
        $this->assertTrue($this->harmony->isNeutral($this->harmony->toHsl('#f5f5f5')));
        $this->assertFalse($this->harmony->isNeutral($this->harmony->toHsl('#1e3a8a')));
    }

    public function test_analogous_colours_score_high(): void
    {
        $items = [
            new Item(['primary_color_hex' => '#1d4ed8', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#0ea5e9', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'solid']),
        ];

        $score = $this->harmony->score($items);

        $this->assertGreaterThanOrEqual(0.85, $score);
    }

    public function test_complementary_pair_is_recognised(): void
    {
        $blue = $this->harmony->toHsl('#0000ff');
        $yellow = $this->harmony->toHsl('#ffff00');

        $this->assertTrue($this->harmony->isComplementary([$blue, $yellow]));
    }

    public function test_triadic_set_is_recognised(): void
    {
        $colours = [
            $this->harmony->toHsl('#ff0000'),
            $this->harmony->toHsl('#00ff00'),
            $this->harmony->toHsl('#0000ff'),
        ];

        $this->assertTrue($this->harmony->isTriadic($colours));
    }

    public function test_too_many_chromatic_colours_are_penalised(): void
    {
        $few = [
            new Item(['primary_color_hex' => '#ef4444', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#22c55e', 'pattern' => 'solid']),
        ];
        $many = [
            new Item(['primary_color_hex' => '#ef4444', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#22c55e', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#3b82f6', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#a855f7', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#f59e0b', 'pattern' => 'solid']),
        ];

        $this->assertTrue(
            $this->harmony->score($many) < $this->harmony->score($few),
            'More chromatic colours should score lower than fewer.'
        );
    }

    public function test_pattern_clash_penalty(): void
    {
        $clashing = [
            new Item(['primary_color_hex' => '#111827', 'pattern' => 'plaid']),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'floral']),
            new Item(['primary_color_hex' => '#1e3a8a', 'pattern' => 'solid']),
        ];

        $clean = [
            new Item(['primary_color_hex' => '#111827', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#e5e7eb', 'pattern' => 'solid']),
            new Item(['primary_color_hex' => '#1e3a8a', 'pattern' => 'solid']),
        ];

        $this->assertTrue(
            $this->harmony->score($clashing) < $this->harmony->score($clean),
            'Busy pattern clashes should score lower than solids.'
        );
    }
}
