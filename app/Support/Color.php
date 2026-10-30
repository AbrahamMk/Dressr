<?php

namespace App\Support;

class Color
{
    /**
     * Filterable colour families the wardrobe index understands.
     *
     * @var list<string>
     */
    private const FAMILIES = [
        'neutral',
        'brown',
        'red',
        'orange',
        'yellow',
        'green',
        'blue',
        'purple',
        'pink',
    ];

    /**
     * @return list<string>
     */
    public static function families(): array
    {
        return self::FAMILIES;
    }

    /**
     * Classify a hex colour into a filterable family.
     */
    public static function family(string $hex): string
    {
        [$hue, $saturation, $lightness] = self::toHsl($hex);

        if ($saturation < 0.15 || $lightness < 0.12 || $lightness > 0.92) {
            return 'neutral';
        }

        if ($hue >= 15 && $hue < 45 && $lightness < 0.45) {
            return 'brown';
        }

        return match (true) {
            $hue < 15 || $hue >= 345 => 'red',
            $hue < 45 => 'orange',
            $hue < 70 => 'yellow',
            $hue < 170 => 'green',
            $hue < 260 => 'blue',
            $hue < 310 => 'purple',
            default => 'pink',
        };
    }

    /**
     * Convert a hex colour to HSL (hue in degrees, saturation and lightness 0-1).
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private static function toHsl(string $hex): array
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
}
