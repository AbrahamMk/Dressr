<?php

namespace App\Services\Outfit;

use App\Models\Item;
use App\Models\User;
use App\Models\WearLog;
use Illuminate\Support\Collection;

/**
 * Loads recent wear history and scores outfit variety.
 */
class VarietyService
{
    /**
     * @return array{item_ids: list<int>, combo_keys: list<string>}
     */
    public function historyFor(User $user): array
    {
        $days = (int) config('stylist.scoring.variety.lookback_days', 14);
        $since = now()->subDays($days)->toDateString();

        $logs = WearLog::query()
            ->where('user_id', $user->id)
            ->whereDate('worn_on', '>=', $since)
            ->with('wearLogItems')
            ->get();

        $itemIds = [];
        $comboKeys = [];

        foreach ($logs as $log) {
            $ids = $log->wearLogItems->pluck('item_id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($ids === []) {
                continue;
            }

            foreach ($ids as $id) {
                $itemIds[$id] = true;
            }

            $comboKeys[] = $this->comboKey($ids);
        }

        return [
            'item_ids' => array_map('intval', array_keys($itemIds)),
            'combo_keys' => array_values(array_unique($comboKeys)),
        ];
    }

    /**
     * Score 0..1. Full score when nothing was recently worn.
     *
     * @param  Collection<int, Item>|list<Item>  $items
     * @param  array{item_ids: list<int>, combo_keys: list<string>}  $history
     */
    public function score(iterable $items, array $history): float
    {
        $items = collect($items)->values();
        $ids = $items->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return 1.0;
        }

        $recent = array_flip($history['item_ids'] ?? []);
        $overlap = $ids->filter(fn (int $id) => isset($recent[$id]))->count();

        $itemPenalty = (float) config('stylist.scoring.variety.item_penalty', 0.12);
        $score = 1.0 - ($overlap * $itemPenalty);

        $key = $this->comboKey($ids->sort()->values()->all());
        if (in_array($key, $history['combo_keys'] ?? [], true)) {
            $score -= (float) config('stylist.scoring.variety.repeat_combo_penalty', 0.35);
        }

        $min = (float) config('stylist.scoring.variety.min_score', 0.15);

        return max($min, min(1.0, round($score, 2)));
    }

    /**
     * @param  list<int>  $ids
     */
    public function comboKey(array $ids): string
    {
        $ids = array_map('intval', $ids);
        sort($ids);

        return implode('-', $ids);
    }
}
