<?php

namespace App\Services\Outfit;

use App\Models\Item;
use App\Models\Measurement;
use App\Models\User;
use App\Services\Silhouette\SilhouetteService;
use Illuminate\Support\Collection;

class OutfitGenerator
{
    public function __construct(
        private readonly OutfitScorer $scorer,
        private readonly SilhouetteService $silhouette,
        private readonly VarietyService $variety,
    ) {}

    /**
     * Generate ranked outfit suggestions for a user.
     *
     * @param  array{occasion: string, season: string|null, temperature: string, count: int}  $options
     * @return array{outfits: list<array<string, mixed>>, message: string|null}
     */
    public function generate(User $user, array $options): array
    {
        $measurement = $user->currentMeasurement();
        $shape = $this->silhouette->classify($measurement);
        $wearHistory = $this->variety->historyFor($user);

        $items = $user->items()
            ->where('is_active', true)
            ->get();

        $filtered = $this->hardFilter($items, $options);

        $byCategory = [
            'top' => $filtered->where('category', 'top')->values(),
            'bottom' => $filtered->where('category', 'bottom')->values(),
            'dress' => $filtered->where('category', 'dress')->values(),
            'outerwear' => $filtered->where('category', 'outerwear')->values(),
            'shoes' => $filtered->where('category', 'shoes')->values(),
            'accessory' => $filtered->where('category', 'accessory')->values(),
        ];

        if ($byCategory['shoes']->isEmpty()) {
            return [
                'outfits' => [],
                'message' => 'Not enough items: add at least one pair of shoes that match the filters.',
            ];
        }

        $hasSeparates = $byCategory['top']->isNotEmpty() && $byCategory['bottom']->isNotEmpty();
        $hasDress = $byCategory['dress']->isNotEmpty();

        if (! $hasSeparates && ! $hasDress) {
            return [
                'outfits' => [],
                'message' => 'Not enough items: need a dress, or both a top and a bottom, that match the filters.',
            ];
        }

        $outerMode = config("stylist.scoring.temperatures.{$options['temperature']}.outerwear", 'optional');
        $outers = $byCategory['outerwear'];

        if ($outerMode === 'exclude') {
            $outers = collect();
        }

        if ($outerMode === 'prefer' && $outers->isEmpty()) {
            // Soften to optional when the wardrobe has no matching outerwear.
            $outerMode = 'optional';
        }

        $combos = $this->buildCombinations($byCategory, $outers, $outerMode);
        $maxCombos = (int) config('stylist.scoring.max_combinations', 2000);

        if (count($combos) > $maxCombos) {
            $combos = array_slice($combos, 0, $maxCombos);
        }

        if ($combos === []) {
            return [
                'outfits' => [],
                'message' => 'Not enough items to build a complete outfit for these filters.',
            ];
        }

        $scored = [];
        foreach ($combos as $combo) {
            $score = $this->scorer->score($combo['items'], $options, $measurement, $shape, $wearHistory);
            $scored[] = [
                'key' => $combo['key'],
                'items' => $combo['items'],
                'slots' => $combo['slots'],
                'score' => $score,
            ];
        }

        usort($scored, fn (array $a, array $b) => $b['score']->total <=> $a['score']->total);

        $count = max(1, min(
            (int) $options['count'],
            (int) config('stylist.scoring.max_count', 10)
        ));

        $picked = $this->dedupeTop($scored, $count);

        return [
            'outfits' => array_map(fn (array $row) => $this->present($row), $picked),
            'message' => null,
        ];
    }

    /**
     * @param  Collection<int, Item>  $items
     * @param  array{occasion: string, season: string|null, temperature: string}  $options
     * @return Collection<int, Item>
     */
    public function hardFilter(Collection $items, array $options): Collection
    {
        $range = config("stylist.scoring.occasions.{$options['occasion']}.formality", [1, 5]);
        [$minFormality, $maxFormality] = $range;
        $season = $options['season'] ?? null;

        return $items->filter(function (Item $item) use ($minFormality, $maxFormality, $season) {
            $formality = (int) $item->formality;

            // Allow one step outside the band so small wardrobes still generate.
            if ($formality < $minFormality - 1 || $formality > $maxFormality + 1) {
                return false;
            }

            if ($season !== null) {
                $seasons = is_array($item->seasons) ? $item->seasons : [];

                if (! in_array($season, $seasons, true)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  array<string, Collection<int, Item>>  $byCategory
     * @param  Collection<int, Item>  $outers
     * @return list<array{key: string, items: list<Item>, slots: array<int, string>}>
     */
    private function buildCombinations(array $byCategory, Collection $outers, string $outerMode): array
    {
        $bases = [];

        foreach ($byCategory['dress'] as $dress) {
            $bases[] = [
                'items' => [$dress],
                'slots' => [$dress->id => 'dress'],
            ];
        }

        foreach ($byCategory['top'] as $top) {
            foreach ($byCategory['bottom'] as $bottom) {
                $bases[] = [
                    'items' => [$top, $bottom],
                    'slots' => [
                        $top->id => 'top',
                        $bottom->id => 'bottom',
                    ],
                ];
            }
        }

        $withShoes = [];
        foreach ($bases as $base) {
            foreach ($byCategory['shoes'] as $shoes) {
                $withShoes[] = [
                    'items' => [...$base['items'], $shoes],
                    'slots' => $base['slots'] + [$shoes->id => 'shoes'],
                ];
            }
        }

        $withOuter = [];
        foreach ($withShoes as $combo) {
            if ($outerMode === 'prefer') {
                foreach ($outers as $outer) {
                    $withOuter[] = [
                        'items' => [...$combo['items'], $outer],
                        'slots' => $combo['slots'] + [$outer->id => 'outer'],
                    ];
                }
            } elseif ($outerMode === 'optional') {
                $withOuter[] = $combo;
                foreach ($outers as $outer) {
                    $withOuter[] = [
                        'items' => [...$combo['items'], $outer],
                        'slots' => $combo['slots'] + [$outer->id => 'outer'],
                    ];
                }
            } else {
                $withOuter[] = $combo;
            }
        }

        $accessories = $byCategory['accessory'];
        $final = [];

        foreach ($withOuter as $combo) {
            $final[] = $this->keyed($combo);

            foreach ($accessories as $accessory) {
                $final[] = $this->keyed([
                    'items' => [...$combo['items'], $accessory],
                    'slots' => $combo['slots'] + [$accessory->id => 'accessory'],
                ]);
            }
        }

        return $final;
    }

    /**
     * @param  array{items: list<Item>, slots: array<int, string>}  $combo
     * @return array{key: string, items: list<Item>, slots: array<int, string>}
     */
    private function keyed(array $combo): array
    {
        $ids = collect($combo['items'])->pluck('id')->sort()->values()->all();

        return [
            'key' => implode('-', $ids),
            'items' => $combo['items'],
            'slots' => $combo['slots'],
        ];
    }

    /**
     * @param  list<array{key: string, items: list<Item>, slots: array<int, string>, score: OutfitScore}>  $scored
     * @return list<array{key: string, items: list<Item>, slots: array<int, string>, score: OutfitScore}>
     */
    private function dedupeTop(array $scored, int $count): array
    {
        $picked = [];
        $seen = [];

        foreach ($scored as $row) {
            if (isset($seen[$row['key']])) {
                continue;
            }

            // Also avoid near-duplicates that share the same core pieces (ignore accessory).
            $coreKey = collect($row['slots'])
                ->reject(fn (string $slot) => $slot === 'accessory')
                ->keys()
                ->sort()
                ->implode('-');

            if (isset($seen['core:'.$coreKey]) && count($picked) >= (int) ceil($count / 2)) {
                continue;
            }

            $seen[$row['key']] = true;
            $seen['core:'.$coreKey] = true;
            $picked[] = $row;

            if (count($picked) >= $count) {
                break;
            }
        }

        return $picked;
    }

    /**
     * @param  array{items: list<Item>, slots: array<int, string>, score: OutfitScore}  $row
     * @return array<string, mixed>
     */
    private function present(array $row): array
    {
        $items = [];

        foreach ($row['items'] as $item) {
            $items[] = [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'slot' => $row['slots'][$item->id] ?? $item->category,
                'primary_color_hex' => $item->primary_color_hex,
                'thumb_url' => $item->thumb_url,
                'photo_url' => $item->photo_url,
                'formality' => $item->formality,
            ];
        }

        return [
            'item_ids' => collect($row['items'])->pluck('id')->values()->all(),
            'slots' => collect($items)
                ->mapWithKeys(fn (array $item) => [$item['slot'] => $item['id']])
                ->all(),
            'items' => $items,
            'score' => $row['score']->total,
            'factors' => $row['score']->factors,
        ];
    }
}
