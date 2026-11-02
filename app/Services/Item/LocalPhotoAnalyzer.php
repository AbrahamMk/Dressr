<?php

namespace App\Services\Item;

use App\Support\Color;
use Illuminate\Http\UploadedFile;

/**
 * Offline heuristic: sample dominant colours from the image.
 * Does not guess category or pattern reliably.
 */
class LocalPhotoAnalyzer
{
    /**
     * @return array{primary_color_hex: string, secondary_color_hex: string|null, color_family: string, source: string, note: string}
     */
    public function analyze(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            return [
                'primary_color_hex' => '#1e3a8a',
                'secondary_color_hex' => null,
                'color_family' => 'blue',
                'source' => 'local',
                'note' => 'Could not read the image for colour sampling. Fill the form manually.',
            ];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $buckets = [];

        $stepX = max(1, (int) floor($width / 40));
        $stepY = max(1, (int) floor($height / 40));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Skip near-white / near-black backgrounds.
                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                if ($max > 245 && $min > 230) {
                    continue;
                }
                if ($max < 25) {
                    continue;
                }

                // Quantise to reduce noise.
                $key = sprintf(
                    '#%02x%02x%02x',
                    (int) (round($r / 16) * 16),
                    (int) (round($g / 16) * 16),
                    (int) (round($b / 16) * 16)
                );

                $buckets[$key] = ($buckets[$key] ?? 0) + 1;
            }
        }

        imagedestroy($image);

        if ($buckets === []) {
            return [
                'primary_color_hex' => '#1e3a8a',
                'secondary_color_hex' => null,
                'color_family' => 'blue',
                'source' => 'local',
                'note' => 'No strong garment colour found. Fill the form manually or add an AI key for better suggestions.',
            ];
        }

        arsort($buckets);
        $hexes = array_keys($buckets);
        $primary = $hexes[0];
        $secondary = $hexes[1] ?? null;

        if ($secondary && Color::family($secondary) === Color::family($primary)) {
            $secondary = $hexes[2] ?? null;
        }

        return [
            'primary_color_hex' => $primary,
            'secondary_color_hex' => $secondary,
            'color_family' => Color::family($primary),
            'source' => 'local',
            'note' => 'Local colour sampling only (no AI key). Review and complete the rest of the form.',
        ];
    }
}
