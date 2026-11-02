<?php

namespace App\Services\Outfit;

use App\Models\Item;
use App\Models\Measurement;
use App\Services\Fit\FitService;
use App\Services\Silhouette\SilhouetteResult;
use App\Services\Silhouette\SilhouetteService;
use Illuminate\Support\Collection;

class OutfitScorer
{
    public function __construct(
        private readonly ColorHarmony $colorHarmony,
        private readonly FitService $fit,
        private readonly SilhouetteService $silhouette,
        private readonly VarietyService $variety,
    ) {}

    /**
     * @param  Collection<int, Item>|list<Item>  $items
     * @param  array{occasion: string, season: string|null, temperature: string}  $context
     * @param  array{item_ids: list<int>, combo_keys: list<string>}  $wearHistory
     */
    public function score(
        iterable $items,
        array $context,
        ?Measurement $measurement,
        SilhouetteResult $shape,
        array $wearHistory = ['item_ids' => [], 'combo_keys' => []],
    ): OutfitScore {
        $items = collect($items)->values();
        $weights = config('stylist.scoring.weights');

        $factors = [
            'color' => $this->colorHarmony->score($items),
            'formality' => $this->formalityScore($items, $context['occasion']),
            'season' => $this->seasonScore($items, $context['season'] ?? null),
            'fit' => $this->fitScore($items, $measurement),
            'silhouette' => $this->silhouetteScore($items, $shape),
            'variety' => $this->variety->score($items, $wearHistory),
        ];

        $total = 0.0;
        foreach ($weights as $key => $weight) {
            $total += ($factors[$key] ?? 0) * (float) $weight;
        }

        return new OutfitScore(
            round($total * 100, 2),
            array_map(fn (float $value) => round($value * 100, 1), $factors),
        );
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function formalityScore(Collection $items, string $occasion): float
    {
        $range = config("stylist.scoring.occasions.{$occasion}.formality");

        if (! is_array($range)) {
            return 0.5;
        }

        [$min, $max] = $range;
        $scores = $items->map(function (Item $item) use ($min, $max) {
            $formality = (int) $item->formality;

            if ($formality >= $min && $formality <= $max) {
                return 1.0;
            }

            $distance = $formality < $min ? $min - $formality : $formality - $max;

            return max(0.0, 1.0 - ($distance * 0.35));
        });

        return round($scores->avg() ?? 0.5, 2);
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function seasonScore(Collection $items, ?string $season): float
    {
        if ($season === null) {
            return 1.0;
        }

        $matches = $items->filter(function (Item $item) use ($season) {
            $seasons = is_array($item->seasons) ? $item->seasons : [];

            return in_array($season, $seasons, true);
        })->count();

        return $items->isEmpty() ? 0.0 : round($matches / $items->count(), 2);
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function fitScore(Collection $items, ?Measurement $measurement): float
    {
        $labelScores = config('stylist.scoring.fit_label_scores', []);

        $scores = $items->map(function (Item $item) use ($measurement, $labelScores) {
            $label = $this->fit->evaluate($measurement, $item)->label;

            return (float) ($labelScores[$label] ?? 0.5);
        });

        return round($scores->avg() ?? 0.5, 2);
    }

    /**
     * @param  Collection<int, Item>  $items
     */
    private function silhouetteScore(Collection $items, SilhouetteResult $shape): float
    {
        if ($shape->shape === null) {
            return 0.5;
        }

        $scores = $items
            ->filter(fn (Item $item) => in_array($item->category, ['top', 'bottom', 'dress', 'outerwear'], true))
            ->map(fn (Item $item) => $this->silhouette->scoreItem($shape, $item));

        if ($scores->isEmpty()) {
            return 0.5;
        }

        return round($scores->avg(), 2);
    }
}
