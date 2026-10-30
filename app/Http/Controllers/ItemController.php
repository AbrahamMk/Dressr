<?php

namespace App\Http\Controllers;

use App\Enums\ItemCategory;
use App\Enums\ItemFitType;
use App\Enums\ItemPattern;
use App\Enums\ItemStretch;
use App\Enums\Season;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Services\PhotoService;
use App\Support\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function __construct(private readonly PhotoService $photos) {}

    /**
     * Show the wardrobe with the active filters applied.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::enum(ItemCategory::class)],
            'season' => ['nullable', Rule::enum(Season::class)],
            'color_family' => ['nullable', Rule::in(Color::families())],
            'search' => ['nullable', 'string', 'max:60'],
        ]);

        $filters = [
            'category' => $validated['category'] ?? null,
            'season' => $validated['season'] ?? null,
            'color_family' => $validated['color_family'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $items = $request->user()->items()
            ->when($filters['category'], fn ($query, $value) => $query->where('category', $value))
            ->when($filters['season'], fn ($query, $value) => $query->whereJsonContains('seasons', $value))
            ->when($filters['color_family'], fn ($query, $value) => $query->where('color_family', $value))
            ->when($filters['search'], fn ($query, $value) => $query->where(function ($query) use ($value) {
                $query->where('name', 'like', "%{$value}%")
                    ->orWhere('subcategory', 'like', "%{$value}%")
                    ->orWhere('material', 'like', "%{$value}%");
            }))
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Wardrobe/Index', [
            'items' => $items,
            'filters' => $filters,
            'options' => [
                'categories' => ItemCategory::values(),
                'seasons' => Season::values(),
                'colorFamilies' => Color::families(),
            ],
        ]);
    }

    /**
     * Show the form for creating an item.
     */
    public function create(): Response
    {
        return Inertia::render('Wardrobe/Form', [
            'item' => null,
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * Store a newly created item.
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['photo']);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['color_family'] = Color::family($data['primary_color_hex']);

        if ($request->hasFile('photo')) {
            $data = array_merge($data, $this->photos->store($request->file('photo')));
        }

        $request->user()->items()->create($data);

        return Redirect::route('wardrobe.index')
            ->with('success', 'Item added to your wardrobe.');
    }

    /**
     * Show the form for editing an item.
     */
    public function edit(Item $item): Response
    {
        $this->authorize('update', $item);

        return Inertia::render('Wardrobe/Form', [
            'item' => $item,
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * Update an existing item.
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $data = $request->validated();
        unset($data['photo']);

        $data['is_active'] = $request->boolean('is_active', $item->is_active);
        $data['color_family'] = Color::family($data['primary_color_hex']);

        if ($request->hasFile('photo')) {
            $this->photos->delete($item->photo_path, $item->thumb_path);
            $data = array_merge($data, $this->photos->store($request->file('photo')));
        }

        $item->update($data);

        return Redirect::route('wardrobe.index')
            ->with('success', 'Item updated.');
    }

    /**
     * Delete an item and its photos.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $this->photos->delete($item->photo_path, $item->thumb_path);

        $item->delete();

        return Redirect::route('wardrobe.index')
            ->with('success', 'Item removed from your wardrobe.');
    }

    /**
     * Select options shared by the create and edit form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => ItemCategory::values(),
            'patterns' => ItemPattern::values(),
            'fitTypes' => ItemFitType::values(),
            'stretches' => ItemStretch::values(),
            'seasons' => Season::values(),
            'cutTags' => config('stylist.cut_tags'),
        ];
    }
}
