<?php

namespace App\Services\Outfit;

use App\Models\Item;

/**
 * Colour harmony scoring for an outfit's item colours and patterns.
 */
class ColorHarmony
{
    /**
     * Score colour harmony for a set of items. Returns 0..1.
     *
     * @param  iterable<Item>  $items
     */
    public function score(iterable $items): float
    {
        $items = collect($items)->values();

        if ($items->isEmpty()) {
            return 1.0;
        }

        $colours = [];
        foreach ($items as $item) {
            $colours[] = $this->toHsl((string) $item->primary_color_hex);

            if (! empty($item->secondary_color_hex)) {
                $colours[] = $this->toHsl((string) $item->secondary_color_hex);
            }
        }

        $chromatic = array_values(array_filter($colours, fn (array $hsl) => ! $this->isNeutral($hsl)));
        $maxChromatic = (int) config('stylist.scoring.color.max_chromatic_before_penalty', 3);
        $over = max(0, count($chromatic) - $maxChromatic);

        // Too many chromatic colours: skip harmony bonuses and apply a flat penalty.
        if ($over > 0) {
            $score = 0.55 - ((float) config('stylist.scoring.color.over_color_penalty', 0.15) * $over);
        } elseif (count($chromatic) <= 1) {
            $score = 0.95;
        } elseif ($this->isAnalogous($chromatic)) {
            $score = 0.9;
        } elseif ($this->isComplementary($chromatic)) {
            $score = 0.85;
        } elseif ($this->isTriadic($chromatic)) {
            $score = 0.8;
        } elseif ($this->huesAreClose($chromatic)) {
            $score = 0.7;
        } else {
            $score = 0.55;
        }

        $score -= $this->patternClashPenalty($items);

        return max(0.0, min(1.0, round($score, 2)));
    }

    /**
     * Convert a hex colour to HSL.
     *
     * @return array{0: float, 1: float, 2: float} hue degrees, saturation 0-1, lightness 0-1
     */
    public function toHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $red = hexdec(substr($hex, 0, 2)) / 255;
        $green = hexdec(substr($hex, 2, 2)) / 255;
        $blue = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $delta = $max - $min;
        $lightness = ($max + $min) / 2;

        if ($delta == 0.0) {
            return [0.0, 0.0, $lightness];
        }

        $saturation = $delta / (1 - abs(2 * $lightness - 1));

        $hue = match ($max) {
            $red => 60 * fmod(($green - $blue) / $delta, 6),
            $green => 60 * ((($blue - $red) / $delta) + 2),
            default => 60 * ((($red - $green) / $delta) + 4),
        };

        if ($hue < 0) {
            $hue += 360;
        }

        return [$hue, $saturation, $lightness];
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $hsl
     */
    public function isNeutral(array $hsl): bool
    {
        [, $saturation, $lightness] = $hsl;

        return $saturation < 0.15 || $lightness < 0.12 || $lightness > 0.92;
    }

    /**
     * @param  list<array{0: float, 1: float, 2: float}>  $colours
     */
    public function isAnalogous(array $colours): bool
    {
        if (count($colours) < 2) {
            return false;
        }

        $max = (float) config('stylist.scoring.color.analogous_max', 40);
        $hues = array_map(fn (array $c) => $c[0], $colours);

        foreach ($hues as $i => $a) {
            foreach ($hues as $j => $b) {
                if ($i >= $j) {
                    continue;
                }

                if ($this->hueDistance($a, $b) > $max) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  list<array{0: float, 1: float, 2: float}>  $colours
     */
    public function isComplementary(array $colours): bool
    {
        if (count($colours) < 2) {
            return false;
        }

        $tolerance = (float) config('stylist.scoring.color.complementary_tolerance', 30);
        $hues = array_map(fn (array $c) => $c[0], $colours);

        for ($i = 0; $i < count($hues); $i++) {
            for ($j = $i + 1; $j < count($hues); $j++) {
                if (abs($this->hueDistance($hues[$i], $hues[$j]) - 180) <= $tolerance) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<array{0: float, 1: float, 2: float}>  $colours
     */
    public function isTriadic(array $colours): bool
    {
        if (count($colours) < 3) {
            return false;
        }

        $tolerance = (float) config('stylist.scoring.color.triadic_tolerance', 30);
        $hues = array_map(fn (array $c) => $c[0], $colours);
        sort($hues);

        // Look for roughly 120° spacing among any three hues.
        for ($i = 0; $i < count($hues); $i++) {
            for ($j = $i + 1; $j < count($hues); $j++) {
                for ($k = $j + 1; $k < count($hues); $k++) {
                    $d1 = $this->hueDistance($hues[$i], $hues[$j]);
                    $d2 = $this->hueDistance($hues[$j], $hues[$k]);
                    $d3 = $this->hueDistance($hues[$k], $hues[$i]);

                    $near120 = fn (float $d) => abs($d - 120) <= $tolerance;

                    if ($near120($d1) && $near120($d2) && $near120($d3)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function hueDistance(float $a, float $b): float
    {
        $diff = abs($a - $b);

        return min($diff, 360 - $diff);
    }

    /**
     * @param  list<array{0: float, 1: float, 2: float}>  $colours
     */
    private function huesAreClose(array $colours): bool
    {
        $hues = array_map(fn (array $c) => $c[0], $colours);
        $spread = 0.0;

        foreach ($hues as $i => $a) {
            foreach ($hues as $j => $b) {
                if ($i >= $j) {
                    continue;
                }
                $spread = max($spread, $this->hueDistance($a, $b));
            }
        }

        return $spread <= 60;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Item>  $items
     */
    private function patternClashPenalty($items): float
    {
        $busy = config('stylist.scoring.color.busy_patterns', []);
        $busyCount = $items
            ->filter(fn (Item $item) => in_array((string) $item->pattern, $busy, true))
            ->count();

        if ($busyCount < 2) {
            return 0.0;
        }

        return (float) config('stylist.scoring.color.pattern_clash_penalty', 0.25) * ($busyCount - 1);
    }
}
