<?php

namespace App\Http\Controllers;

use App\Enums\OutfitSource;
use App\Http\Requests\GenerateOutfitRequest;
use App\Http\Requests\StoreOutfitRequest;
use App\Http\Requests\UpdateOutfitRequest;
use App\Models\Outfit;
use App\Services\Outfit\OutfitGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class OutfitController extends Controller
{
    public function __construct(private readonly OutfitGenerator $generator) {}

    /**
     * List saved outfits.
     */
    public function index(Request $request): Response
    {
        $favoritesOnly = $request->boolean('favorites');

        $outfits = $request->user()->outfits()
            ->with(['outfitItems.item'])
            ->when($favoritesOnly, fn ($query) => $query->where('is_favorite', true))
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Outfit $outfit) => $this->presentOutfit($outfit));

        return Inertia::render('Outfits/Index', [
            'outfits' => $outfits,
            'filters' => [
                'favorites' => $favoritesOnly,
            ],
            'options' => $this->options(),
        ]);
    }

    /**
     * Show the generator form.
     */
    public function generate(): Response
    {
        return Inertia::render('Outfits/Generate', [
            'options' => $this->options(),
            'defaults' => [
                'occasion' => 'casual',
                'season' => 'summer',
                'temperature' => 'mild',
                'count' => (int) config('stylist.scoring.default_count', 5),
            ],
        ]);
    }

    /**
     * Run the rule-based generator and return JSON results.
     */
    public function generateResults(GenerateOutfitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['count'] = (int) ($data['count'] ?? config('stylist.scoring.default_count', 5));
        $data['season'] = $data['season'] ?? null;

        $result = $this->generator->generate($request->user(), $data);

        return response()->json($result);
    }

    /**
     * Persist a generated outfit.
     */
    public function store(StoreOutfitRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $outfit = DB::transaction(function () use ($request, $data) {
            /** @var Outfit $outfit */
            $outfit = $request->user()->outfits()->create([
                'name' => $data['name'],
                'occasion' => $data['occasion'],
                'season' => $data['season'] ?? null,
                'score' => $data['score'] ?? null,
                'source' => OutfitSource::Rules->value,
                'reasoning' => null,
                'is_favorite' => false,
            ]);

            foreach ($data['slots'] as $slot => $itemId) {
                $outfit->outfitItems()->create([
                    'item_id' => (int) $itemId,
                    'slot' => (string) $slot,
                ]);
            }

            return $outfit;
        });

        return Redirect::route('outfits.index')
            ->with('success', "Saved “{$outfit->name}”.");
    }

    /**
     * Update name or favorite flag.
     */
    public function update(UpdateOutfitRequest $request, Outfit $outfit): RedirectResponse
    {
        $this->authorize('update', $outfit);

        $outfit->update($request->validated());

        return Redirect::back()->with('success', 'Outfit updated.');
    }

    /**
     * Toggle favorite.
     */
    public function favorite(Outfit $outfit): RedirectResponse
    {
        $this->authorize('update', $outfit);

        $outfit->update(['is_favorite' => ! $outfit->is_favorite]);

        return Redirect::back()->with(
            'success',
            $outfit->is_favorite ? 'Added to favorites.' : 'Removed from favorites.'
        );
    }

    /**
     * Delete a saved outfit.
     */
    public function destroy(Outfit $outfit): RedirectResponse
    {
        $this->authorize('delete', $outfit);

        $outfit->delete();

        return Redirect::route('outfits.index')
            ->with('success', 'Outfit deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        $occasions = collect(config('stylist.scoring.occasions'))
            ->map(fn (array $meta, string $key) => [
                'value' => $key,
                'label' => $meta['label'],
            ])
            ->values()
            ->all();

        $temperatures = collect(config('stylist.scoring.temperatures'))
            ->map(fn (array $meta, string $key) => [
                'value' => $key,
                'label' => $meta['label'],
            ])
            ->values()
            ->all();

        return [
            'occasions' => $occasions,
            'temperatures' => $temperatures,
            'seasons' => ['spring', 'summer', 'autumn', 'winter'],
            'maxCount' => (int) config('stylist.scoring.max_count', 10),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentOutfit(Outfit $outfit): array
    {
        $items = $outfit->outfitItems->map(function ($pivot) {
            $item = $pivot->item;

            return [
                'id' => $item?->id,
                'name' => $item?->name,
                'category' => $item?->category,
                'slot' => $pivot->slot,
                'primary_color_hex' => $item?->primary_color_hex,
                'thumb_url' => $item?->thumb_url,
            ];
        })->values()->all();

        return [
            'id' => $outfit->id,
            'name' => $outfit->name,
            'occasion' => $outfit->occasion,
            'season' => $outfit->season,
            'score' => $outfit->score !== null ? (float) $outfit->score : null,
            'source' => $outfit->source,
            'is_favorite' => $outfit->is_favorite,
            'items' => $items,
            'created_at' => $outfit->created_at?->toDateString(),
        ];
    }
}
