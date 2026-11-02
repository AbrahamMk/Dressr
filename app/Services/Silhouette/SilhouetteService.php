<?php

namespace App\Services\Silhouette;

use App\Models\Item;
use App\Models\Measurement;

/**
 * Body-shape classifier and cut-tag scorer. No database access.
 */
class SilhouetteService
{
    /**
     * Classify a body shape from chest / waist / hips.
     */
    public function classify(?Measurement $measurement): SilhouetteResult
    {
        if ($measurement === null) {
            return new SilhouetteResult(null, null, null);
        }

        $chest = (float) $measurement->chest_cm;
        $waist = (float) $measurement->waist_cm;
        $hips = (float) $measurement->hips_cm;

        if ($chest <= 0 || $waist <= 0 || $hips <= 0) {
            return new SilhouetteResult(null, null, null);
        }

        $thresholds = config('stylist.silhouette.thresholds');
        $order = config('stylist.silhouette.evaluation_order', []);

        foreach ($order as $shape) {
            if ($this->matches($shape, $chest, $waist, $hips, $thresholds)) {
                return $this->resultFor($shape);
            }
        }

        // Defensive fallback — rectangle is last in the order and should always match.
        return $this->resultFor('rectangle');
    }

    /**
     * Score how well an item's cut tags suit a classified shape. Returns 0..1.
     */
    public function scoreItem(SilhouetteResult $shape, Item $item): float
    {
        if ($shape->shape === null) {
            return 0.5;
        }

        $tags = is_array($item->cut_tags) ? $item->cut_tags : [];
        $config = config('stylist.silhouette.item_score');
        $score = (float) $config['base'];

        foreach ($tags as $tag) {
            if (in_array($tag, $shape->favored, true)) {
                $score += (float) $config['favored_weight'];
            }

            if (in_array($tag, $shape->avoided, true)) {
                $score -= (float) $config['avoided_weight'];
            }
        }

        return max(0.0, min(1.0, round($score, 2)));
    }

    /**
     * Favored and avoided cut tags for a shape key.
     *
     * @return array{favored: list<string>, avoided: list<string>}
     */
    public function cutPreferences(string $shape): array
    {
        $config = config("stylist.silhouette.shapes.{$shape}", []);

        return [
            'favored' => $config['favored'] ?? [],
            'avoided' => $config['avoided'] ?? [],
        ];
    }

    /**
     * @param  array<string, float>  $thresholds
     */
    private function matches(string $shape, float $chest, float $waist, float $hips, array $thresholds): bool
    {
        $avgChestHips = ($chest + $hips) / 2;
        $balancedDiff = abs($chest - $hips) / max($avgChestHips, 0.001);
        $smaller = min($chest, $hips);
        $definedWaist = $waist / max($smaller, 0.001) <= $thresholds['defined_waist'];

        $isRectangle = $this->isRectangle($chest, $waist, $hips, $thresholds['rectangle_band']);

        return match ($shape) {
            'hourglass' => $balancedDiff <= $thresholds['balanced_diff'] && $definedWaist,
            'pear' => ($hips / max($chest, 0.001)) >= $thresholds['pear_hips_ratio'],
            'inverted_triangle' => ($chest / max($hips, 0.001)) >= $thresholds['inverted_chest_ratio']
                && ($waist / max($chest, 0.001)) < 0.92,
            'apple' => ! $isRectangle
                && ($waist / max($hips, 0.001)) >= $thresholds['apple_waist_hips']
                && ($waist / max($chest, 0.001)) >= 0.85
                && ! $definedWaist,
            'rectangle' => $isRectangle,
            default => false,
        };
    }

    private function isRectangle(float $chest, float $waist, float $hips, float $band): bool
    {
        $avg = ($chest + $waist + $hips) / 3;

        return abs($chest - $avg) / $avg <= $band
            && abs($waist - $avg) / $avg <= $band
            && abs($hips - $avg) / $avg <= $band;
    }

    private function resultFor(string $shape): SilhouetteResult
    {
        $config = config("stylist.silhouette.shapes.{$shape}", []);

        return new SilhouetteResult(
            $shape,
            $config['label'] ?? $shape,
            $config['explanation'] ?? null,
            $config['favored'] ?? [],
            $config['avoided'] ?? [],
        );
    }
}
