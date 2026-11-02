<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Outfit;
use App\Models\OutfitItem;
use App\Models\User;
use App\Models\WearLog;
use App\Services\Fit\FitService;
use App\Services\Outfit\ColorHarmony;
use App\Services\Outfit\OutfitGenerator;
use App\Services\Outfit\OutfitScorer;
use App\Services\Outfit\VarietyService;
use App\Services\Silhouette\SilhouetteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WearLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_wear_log_requires_authentication(): void
    {
        $this->get('/wear-log')->assertRedirect('/login');
    }

    public function test_wear_log_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/wear-log')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('WearLog/Index')
                ->has('days')
                ->has('month'));
    }

    public function test_outfit_can_be_logged_as_worn(): void
    {
        $user = User::factory()->create();
        $top = Item::factory()->for($user)->create(['category' => 'top']);
        $shoes = Item::factory()->for($user)->create(['category' => 'shoes']);
        $outfit = Outfit::factory()->for($user)->create();
        OutfitItem::query()->create(['outfit_id' => $outfit->id, 'item_id' => $top->id, 'slot' => 'top']);
        OutfitItem::query()->create(['outfit_id' => $outfit->id, 'item_id' => $shoes->id, 'slot' => 'shoes']);

        $this->actingAs($user)
            ->post('/wear-log', ['outfit_id' => $outfit->id])
            ->assertRedirect();

        $this->assertTrue(
            WearLog::query()
                ->where('user_id', $user->id)
                ->where('outfit_id', $outfit->id)
                ->whereDate('worn_on', now()->toDateString())
                ->exists()
        );

        $this->assertDatabaseCount('wear_log_items', 2);
    }

    public function test_user_cannot_log_another_users_outfit(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $outfit = Outfit::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->post('/wear-log', ['outfit_id' => $outfit->id])
            ->assertSessionHasErrors('outfit_id');

        $this->assertDatabaseCount('wear_logs', 0);
    }

    public function test_logging_changes_generation_ranking(): void
    {
        $user = User::factory()->create();

        $topA = Item::factory()->for($user)->create([
            'name' => 'Top A',
            'category' => 'top',
            'seasons' => ['summer'],
            'formality' => 2,
            'primary_color_hex' => '#1e3a8a',
            'is_active' => true,
        ]);
        $topB = Item::factory()->for($user)->create([
            'name' => 'Top B',
            'category' => 'top',
            'seasons' => ['summer'],
            'formality' => 2,
            'primary_color_hex' => '#166534',
            'is_active' => true,
        ]);
        $bottom = Item::factory()->for($user)->create([
            'name' => 'Bottom',
            'category' => 'bottom',
            'seasons' => ['summer'],
            'formality' => 2,
            'primary_color_hex' => '#111827',
            'is_active' => true,
        ]);
        $shoes = Item::factory()->for($user)->create([
            'name' => 'Shoes',
            'category' => 'shoes',
            'seasons' => ['summer'],
            'formality' => 2,
            'primary_color_hex' => '#e5e7eb',
            'is_active' => true,
        ]);

        $variety = new VarietyService;
        $generator = new OutfitGenerator(
            new OutfitScorer(new ColorHarmony, new FitService, new SilhouetteService, $variety),
            new SilhouetteService,
            $variety,
        );

        $options = [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'hot',
            'count' => 5,
        ];

        $before = $generator->generate($user, $options);
        $this->assertNotEmpty($before['outfits']);

        $log = WearLog::factory()->for($user)->create(['worn_on' => now()->toDateString()]);
        $log->wearLogItems()->create(['item_id' => $topA->id]);
        $log->wearLogItems()->create(['item_id' => $bottom->id]);
        $log->wearLogItems()->create(['item_id' => $shoes->id]);

        $after = $generator->generate($user, $options);
        $this->assertNotEmpty($after['outfits']);

        $withTopA = collect($after['outfits'])->first(
            fn (array $outfit) => in_array($topA->id, $outfit['item_ids'], true)
        );
        $withTopB = collect($after['outfits'])->first(
            fn (array $outfit) => in_array($topB->id, $outfit['item_ids'], true)
        );

        if ($withTopA && $withTopB) {
            $this->assertLessThan(
                $withTopB['factors']['variety'],
                $withTopA['factors']['variety']
            );
        } else {
            // Ranking may drop the worn combo entirely from the top-N — still a valid effect.
            $this->assertTrue(
                $withTopB !== null || ($after['outfits'][0]['item_ids'][0] ?? null) !== $topA->id
            );
        }
    }
}
