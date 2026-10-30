<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Blue Oxford Shirt',
            'category' => 'top',
            'subcategory' => 'shirt',
            'primary_color_hex' => '#1e3a8a',
            'secondary_color_hex' => null,
            'pattern' => 'solid',
            'material' => 'cotton',
            'seasons' => ['spring', 'summer'],
            'formality' => 3,
            'fit_type' => 'regular',
            'stretch' => 'none',
            'cut_tags' => ['structured_shoulder'],
            'size_label' => 'M',
            'is_active' => true,
        ], $overrides);
    }

    public function test_wardrobe_requires_authentication(): void
    {
        $this->get('/wardrobe')->assertRedirect('/login');
    }

    public function test_wardrobe_index_is_displayed(): void
    {
        $user = User::factory()->create();
        Item::factory()->for($user)->count(3)->create();

        $this->actingAs($user)
            ->get('/wardrobe')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Wardrobe/Index')
                ->has('items.data', 3)
                ->has('options.categories'));
    }

    public function test_item_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/wardrobe', $this->payload());

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('wardrobe.index'));

        $this->assertDatabaseHas('items', [
            'user_id' => $user->id,
            'name' => 'Blue Oxford Shirt',
            'category' => 'top',
            'color_family' => 'blue',
        ]);

        $item = Item::firstOrFail();

        $this->assertSame(['spring', 'summer'], $item->seasons);
        $this->assertSame(['structured_shoulder'], $item->cut_tags);
        $this->assertTrue($item->is_active);
    }

    public function test_photo_is_stored_with_a_thumbnail(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/wardrobe', $this->payload([
                'photo' => UploadedFile::fake()->image('coat.jpg', 2000, 1600),
            ]))
            ->assertSessionHasNoErrors();

        $item = Item::firstOrFail();

        $this->assertNotNull($item->photo_path);
        $this->assertNotNull($item->thumb_path);

        Storage::disk('public')->assertExists($item->photo_path);
        Storage::disk('public')->assertExists($item->thumb_path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($item->photo_path));
        $this->assertLessThanOrEqual(1200, max($width, $height));

        [$thumbWidth, $thumbHeight] = getimagesize(Storage::disk('public')->path($item->thumb_path));
        $this->assertLessThanOrEqual(300, max($thumbWidth, $thumbHeight));
    }

    public function test_item_validation_rejects_bad_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/wardrobe', $this->payload([
                'category' => 'hat',
                'primary_color_hex' => 'blue',
                'formality' => 9,
                'seasons' => ['monsoon'],
                'cut_tags' => ['not_a_real_tag'],
                'photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors([
                'category',
                'primary_color_hex',
                'formality',
                'seasons.0',
                'cut_tags.0',
                'photo',
            ]);

        $this->assertDatabaseCount('items', 0);
    }

    public function test_item_can_be_updated_without_replacing_the_photo(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->for($user)->create(['name' => 'Old name']);

        $this->actingAs($user)
            ->patch(route('wardrobe.update', $item), $this->payload(['name' => 'New name']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('wardrobe.index'));

        $this->assertSame('New name', $item->refresh()->name);
    }

    public function test_replacing_the_photo_deletes_the_old_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/wardrobe', $this->payload([
                'photo' => UploadedFile::fake()->image('first.jpg', 800, 800),
            ]));

        $item = Item::firstOrFail();
        $oldPhoto = $item->photo_path;
        $oldThumb = $item->thumb_path;

        $this->actingAs($user)
            ->patch(route('wardrobe.update', $item), $this->payload([
                'photo' => UploadedFile::fake()->image('second.jpg', 800, 800),
            ]))
            ->assertSessionHasNoErrors();

        $item->refresh();

        $this->assertNotSame($oldPhoto, $item->photo_path);
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertMissing($oldThumb);
        Storage::disk('public')->assertExists($item->photo_path);
    }

    public function test_item_and_its_files_are_deleted(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/wardrobe', $this->payload([
                'photo' => UploadedFile::fake()->image('jacket.jpg', 800, 800),
            ]));

        $item = Item::firstOrFail();

        $this->actingAs($user)
            ->delete(route('wardrobe.destroy', $item))
            ->assertRedirect(route('wardrobe.index'));

        $this->assertDatabaseCount('items', 0);
        Storage::disk('public')->assertMissing($item->photo_path);
        Storage::disk('public')->assertMissing($item->thumb_path);
    }

    public function test_wardrobe_can_be_filtered(): void
    {
        $user = User::factory()->create();

        Item::factory()->for($user)->create([
            'category' => 'top',
            'seasons' => ['summer'],
            'color_family' => 'blue',
            'name' => 'Linen Shirt',
            'material' => 'linen',
        ]);
        Item::factory()->for($user)->create([
            'category' => 'shoes',
            'seasons' => ['winter'],
            'color_family' => 'neutral',
            'name' => 'Leather Boots',
            'material' => 'leather',
        ]);

        $this->actingAs($user)
            ->get('/wardrobe?category=shoes')
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.category', 'shoes'));

        $this->actingAs($user)
            ->get('/wardrobe?season=summer')
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Linen Shirt'));

        $this->actingAs($user)
            ->get('/wardrobe?color_family=neutral')
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Leather Boots'));

        $this->actingAs($user)
            ->get('/wardrobe?search=linen')
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Linen Shirt'));
    }

    public function test_user_only_sees_their_own_items(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Item::factory()->for($user)->create(['name' => 'Mine']);
        Item::factory()->for($other)->create(['name' => 'Theirs']);

        $this->actingAs($user)
            ->get('/wardrobe')
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Mine'));
    }

    public function test_user_cannot_edit_update_or_delete_another_users_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = Item::factory()->for($owner)->create(['name' => 'Owner item']);

        $this->actingAs($intruder)
            ->get(route('wardrobe.edit', $item))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch(route('wardrobe.update', $item), $this->payload(['name' => 'Hijacked']))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('wardrobe.destroy', $item))
            ->assertForbidden();

        $this->assertSame('Owner item', $item->refresh()->name);
    }
}
