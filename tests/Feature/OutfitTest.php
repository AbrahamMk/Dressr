<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Outfit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OutfitTest extends TestCase
{
    use RefreshDatabase;

    public function test_outfits_index_requires_authentication(): void
    {
        $this->get('/outfits')->assertRedirect('/login');
    }

    public function test_generate_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/outfits/generate')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Outfits/Generate')
                ->has('options.occasions')
                ->has('defaults'));
    }

    public function test_generate_returns_outfits_as_json(): void
    {
        $user = User::factory()->create();
        $this->seedMinimalWardrobe($user);

        $response = $this->actingAs($user)->postJson('/outfits/generate', [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'mild',
            'count' => 3,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'outfits' => [
                    ['item_ids', 'slots', 'items', 'score', 'factors'],
                ],
                'message',
            ]);

        $this->assertNotEmpty($response->json('outfits'));
    }

    public function test_generate_handles_not_enough_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/outfits/generate', [
            'occasion' => 'casual',
            'season' => 'summer',
            'temperature' => 'mild',
            'count' => 3,
        ]);

        $response->assertOk()
            ->assertJson([
                'outfits' => [],
            ]);

        $this->assertStringContainsString('Not enough items', $response->json('message'));
    }

    public function test_outfit_can_be_saved(): void
    {
        $user = User::factory()->create();
        $top = Item::factory()->for($user)->create(['category' => 'top', 'seasons' => ['summer']]);
        $bottom = Item::factory()->for($user)->create(['category' => 'bottom', 'seasons' => ['summer']]);
        $shoes = Item::factory()->for($user)->create(['category' => 'shoes', 'seasons' => ['summer']]);

        $response = $this->actingAs($user)->post('/outfits', [
            'name' => 'Weekend look',
            'occasion' => 'casual',
            'season' => 'summer',
            'score' => 82.5,
            'item_ids' => [$top->id, $bottom->id, $shoes->id],
            'slots' => [
                'top' => $top->id,
                'bottom' => $bottom->id,
                'shoes' => $shoes->id,
            ],
        ]);

        $response->assertRedirect(route('outfits.index'));

        $this->assertDatabaseHas('outfits', [
            'user_id' => $user->id,
            'name' => 'Weekend look',
            'source' => 'rules',
        ]);

        $outfit = Outfit::firstOrFail();
        $this->assertCount(3, $outfit->outfitItems);
    }

    public function test_outfit_can_be_favorited_and_deleted(): void
    {
        $user = User::factory()->create();
        $outfit = Outfit::factory()->for($user)->create(['is_favorite' => false]);

        $this->actingAs($user)
            ->post(route('outfits.favorite', $outfit))
            ->assertRedirect();

        $this->assertTrue($outfit->refresh()->is_favorite);

        $this->actingAs($user)
            ->delete(route('outfits.destroy', $outfit))
            ->assertRedirect(route('outfits.index'));

        $this->assertDatabaseCount('outfits', 0);
    }

    public function test_user_cannot_modify_another_users_outfit(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $outfit = Outfit::factory()->for($owner)->create(['name' => 'Mine']);

        $this->actingAs($intruder)
            ->post(route('outfits.favorite', $outfit))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch(route('outfits.update', $outfit), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('outfits.destroy', $outfit))
            ->assertForbidden();

        $this->assertSame('Mine', $outfit->refresh()->name);
    }

    public function test_favorites_filter_on_index(): void
    {
        $user = User::factory()->create();
        Outfit::factory()->for($user)->create(['name' => 'Plain', 'is_favorite' => false]);
        Outfit::factory()->for($user)->favorite()->create(['name' => 'Star']);

        $this->actingAs($user)
            ->get('/outfits?favorites=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Outfits/Index')
                ->has('outfits.data', 1)
                ->where('outfits.data.0.name', 'Star'));
    }

    private function seedMinimalWardrobe(User $user): void
    {
        Item::factory()->for($user)->create([
            'category' => 'top',
            'seasons' => ['summer'],
            'formality' => 2,
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'category' => 'bottom',
            'seasons' => ['summer'],
            'formality' => 2,
            'is_active' => true,
        ]);
        Item::factory()->for($user)->create([
            'category' => 'shoes',
            'seasons' => ['summer'],
            'formality' => 2,
            'is_active' => true,
        ]);
    }
}
