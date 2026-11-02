<?php

namespace App\Services\Fit;

use App\Models\Item;
use App\Models\Measurement;

/**
 * Pure fit evaluator. No database access.
 */
class FitService
{
    /**
     * Evaluate how well an item fits a body measurement set.
     */
    public function evaluate(?Measurement $measurement, Item $item): FitResult
    {
        if ($measurement === null) {
            return new FitResult(null, 'unknown', []);
        }

        $category = (string) $item->category;
        $pairs = config("stylist.fit.category_pairs.{$category}", []);

        if ($pairs === []) {
            return new FitResult(null, 'unknown', []);
        }

        $pairs = $this->filterPairs($pairs, $item);

        if ($pairs === []) {
            return new FitResult(null, 'unknown', []);
        }

        $stretch = (string) ($item->stretch ?? 'none');
        $allowance = (float) config("stylist.fit.stretch_allowance.{$stretch}", 0);
        $outerExtra = $category === 'outerwear'
            ? (float) config('stylist.fit.outerwear_extra_ease', 0)
            : 0.0;

        $eases = [];
        $pairScores = [];
        $labels = [];

        foreach ($pairs as $pair) {
            $pairConfig = config("stylist.fit.pairs.{$pair}");
            $easeConfig = config("stylist.fit.ease.{$pair}");

            if (! is_array($pairConfig) || ! is_array($easeConfig)) {
                continue;
            }

            $bodyKey = $pairConfig['body'];
            $garmentKey = $pairConfig['garment'];
            $mode = $pairConfig['mode'];

            $body = $measurement->{$bodyKey};
            $garment = $item->{$garmentKey};

            if ($body === null || $garment === null) {
                $eases[] = [
                    'pair' => $pair,
                    'ease' => null,
                    'label' => 'unknown',
                    'body' => $body !== null ? (float) $body : null,
                    'garment' => $garment !== null ? (float) $garment : null,
                ];
                $labels[] = 'unknown';

                continue;
            }

            $bodyValue = (float) $body;
            $garmentValue = (float) $garment;
            $ease = $mode === 'circumference'
                ? ($garmentValue * 2) - $bodyValue
                : $garmentValue - $bodyValue;

            $good = $this->adjustRange($easeConfig['good'], $allowance, $outerExtra);
            $acceptable = $this->adjustRange($easeConfig['acceptable'], $allowance, $outerExtra);

            $pairLabel = $this->labelForEase($ease, $good, $acceptable);
            $pairScore = $this->scoreForEase($ease, $good, $acceptable);

            $eases[] = [
                'pair' => $pair,
                'ease' => round($ease, 1),
                'label' => $pairLabel,
                'body' => $bodyValue,
                'garment' => $garmentValue,
            ];
            $labels[] = $pairLabel;
            $pairScores[] = $pairScore;
        }

        if ($eases === []) {
            return new FitResult(null, 'unknown', []);
        }

        // Need at least one measurable pair to score; all-unknown stays unknown.
        $known = array_values(array_filter($labels, fn (string $label) => $label !== 'unknown'));

        if ($known === []) {
            return new FitResult(null, 'unknown', $eases);
        }

        $overallLabel = $this->overallLabel($known);
        $score = array_sum($pairScores) / count($pairScores);

        return new FitResult(round($score, 2), $overallLabel, $eases);
    }

    /**
     * @param  list<string>  $pairs
     * @return list<string>
     */
    private function filterPairs(array $pairs, Item $item): array
    {
        if (! in_array('inseam', $pairs, true)) {
            return $pairs;
        }

        $tags = is_array($item->cut_tags) ? $item->cut_tags : [];
        $skipTags = config('stylist.fit.skip_inseam_cut_tags', []);

        if (array_intersect($tags, $skipTags) !== []) {
            return array_values(array_filter($pairs, fn (string $pair) => $pair !== 'inseam'));
        }

        $sub = strtolower((string) $item->subcategory);
        $skipSubs = config('stylist.fit.skip_inseam_subcategories', []);

        foreach ($skipSubs as $needle) {
            if ($sub === $needle || str_contains($sub, $needle)) {
                return array_values(array_filter($pairs, fn (string $pair) => $pair !== 'inseam'));
            }
        }

        return $pairs;
    }

    /**
     * @param  array{0: float, 1: float}  $range
     * @return array{0: float, 1: float}
     */
    private function adjustRange(array $range, float $allowance, float $outerExtra): array
    {
        // Stretch and outerwear both widen the window (lower min, raise max).
        return [
            $range[0] - $allowance - $outerExtra,
            $range[1] + $outerExtra,
        ];
    }

    /**
     * @param  array{0: float, 1: float}  $good
     * @param  array{0: float, 1: float}  $acceptable
     */
    private function labelForEase(float $ease, array $good, array $acceptable): string
    {
        if ($ease >= $good[0] && $ease <= $good[1]) {
            return 'good_fit';
        }

        if ($ease >= $acceptable[0] && $ease <= $acceptable[1]) {
            return 'acceptable';
        }

        return $ease < $acceptable[0] ? 'tight' : 'loose';
    }

    /**
     * Map ease distance into 0..1. Peak at the midpoint of the good range.
     *
     * @param  array{0: float, 1: float}  $good
     * @param  array{0: float, 1: float}  $acceptable
     */
    private function scoreForEase(float $ease, array $good, array $acceptable): float
    {
        if ($ease >= $good[0] && $ease <= $good[1]) {
            $mid = ($good[0] + $good[1]) / 2;
            $half = max(($good[1] - $good[0]) / 2, 0.001);
            $distance = abs($ease - $mid) / $half;

            return max(0.85, 1.0 - ($distance * 0.15));
        }

        if ($ease >= $acceptable[0] && $ease <= $acceptable[1]) {
            return 0.7;
        }

        // Outside acceptable: decay with distance from the nearest bound.
        $bound = $ease < $acceptable[0] ? $acceptable[0] : $acceptable[1];
        $distance = abs($ease - $bound);
        $penalty = min(0.55, $distance / 10);

        return max(0.05, 0.4 - $penalty);
    }

    /**
     * @param  list<string>  $labels
     */
    private function overallLabel(array $labels): string
    {
        if (in_array('tight', $labels, true)) {
            return 'tight';
        }

        if (in_array('loose', $labels, true)) {
            return 'loose';
        }

        if (in_array('acceptable', $labels, true)) {
            return 'acceptable';
        }

        return 'good_fit';
    }
}
