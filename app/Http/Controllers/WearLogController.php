<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWearLogRequest;
use App\Models\Outfit;
use App\Models\WearLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class WearLogController extends Controller
{
    /**
     * Month calendar of what was worn.
     */
    public function index(Request $request): Response
    {
        $month = $request->input('month', now()->format('Y-m'));

        try {
            $cursor = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $cursor = now()->startOfMonth();
            $month = $cursor->format('Y-m');
        }

        $start = $cursor->copy()->startOfMonth();
        $end = $cursor->copy()->endOfMonth();

        $logs = $request->user()->wearLogs()
            ->with(['wearLogItems.item', 'outfit'])
            ->whereBetween('worn_on', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('worn_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WearLog $log) => $this->present($log));

        $byDate = $logs->groupBy(fn (array $log) => $log['worn_on']);

        $days = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $days[] = [
                'date' => $key,
                'day' => $day->day,
                'weekday' => $day->dayOfWeek,
                'count' => count($byDate->get($key, [])),
                'logs' => $byDate->get($key, collect())->values()->all(),
            ];
        }

        $selected = $request->input('date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $selected)) {
            $selected = now()->toDateString();
        }

        return Inertia::render('WearLog/Index', [
            'month' => $month,
            'days' => $days,
            'selectedDate' => $selected,
            'selectedLogs' => collect($byDate->get($selected, []))->values()->all(),
            'prevMonth' => $cursor->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $cursor->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * Log an outfit (or free item set) as worn.
     */
    public function store(StoreWearLogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $wornOn = $data['worn_on'] ?? now()->toDateString();

        $itemIds = $data['item_ids'] ?? [];
        $outfitId = $data['outfit_id'] ?? null;

        if ($outfitId) {
            $outfit = Outfit::query()
                ->where('user_id', $request->user()->id)
                ->with('outfitItems')
                ->findOrFail($outfitId);

            $itemIds = $outfit->outfitItems->pluck('item_id')->all();
        }

        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        DB::transaction(function () use ($request, $outfitId, $wornOn, $itemIds) {
            /** @var WearLog $log */
            $log = $request->user()->wearLogs()->create([
                'outfit_id' => $outfitId,
                'worn_on' => $wornOn,
            ]);

            foreach ($itemIds as $itemId) {
                $log->wearLogItems()->create(['item_id' => $itemId]);
            }
        });

        return Redirect::back()->with('success', 'Logged as worn.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(WearLog $log): array
    {
        return [
            'id' => $log->id,
            'worn_on' => $log->worn_on?->toDateString(),
            'outfit_id' => $log->outfit_id,
            'outfit_name' => $log->outfit?->name,
            'items' => $log->wearLogItems->map(fn ($pivot) => [
                'id' => $pivot->item?->id,
                'name' => $pivot->item?->name,
                'thumb_url' => $pivot->item?->thumb_url,
                'primary_color_hex' => $pivot->item?->primary_color_hex,
            ])->values()->all(),
        ];
    }
}
