<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Measurement;
use App\Models\User;
use App\Support\Color;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoWardrobeSeeder extends Seeder
{
    /**
     * Realistic demo user with measurements and ~30 wardrobe items.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'demo@dressr.test'],
            [
                'name' => 'Demo Stylist',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        Measurement::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'measured_on' => now()->toDateString(),
            ],
            [
                'height_cm' => 175,
                'weight_kg' => 72,
                'shoulder_width_cm' => 44,
                'chest_cm' => 98,
                'waist_cm' => 80,
                'hips_cm' => 100,
                'inseam_cm' => 80,
                'arm_length_cm' => 62,
            ]
        );

        $user->items()->delete();

        foreach ($this->catalogue() as $row) {
            $hex = $row['primary_color_hex'];

            Item::query()->create([
                'user_id' => $user->id,
                'name' => $row['name'],
                'category' => $row['category'],
                'subcategory' => $row['subcategory'],
                'primary_color_hex' => $hex,
                'secondary_color_hex' => $row['secondary_color_hex'] ?? null,
                'color_family' => Color::family($hex),
                'pattern' => $row['pattern'] ?? 'solid',
                'material' => $row['material'] ?? null,
                'seasons' => $row['seasons'],
                'formality' => $row['formality'],
                'fit_type' => $row['fit_type'] ?? 'regular',
                'stretch' => $row['stretch'] ?? 'none',
                'cut_tags' => $row['cut_tags'] ?? [],
                'size_label' => $row['size_label'] ?? 'M',
                'g_chest_width' => $row['g_chest_width'] ?? null,
                'g_waist_width' => $row['g_waist_width'] ?? null,
                'g_hip_width' => $row['g_hip_width'] ?? null,
                'g_inseam' => $row['g_inseam'] ?? null,
                'g_shoulder_width' => $row['g_shoulder_width'] ?? null,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            // Tops
            ['name' => 'White Oxford Shirt', 'category' => 'top', 'subcategory' => 'shirt', 'primary_color_hex' => '#f8fafc', 'seasons' => ['spring', 'summer', 'autumn'], 'formality' => 3, 'cut_tags' => ['structured_shoulder', 'fitted'], 'g_chest_width' => 54, 'g_shoulder_width' => 44],
            ['name' => 'Navy Polo', 'category' => 'top', 'subcategory' => 'polo', 'primary_color_hex' => '#1e3a8a', 'seasons' => ['spring', 'summer'], 'formality' => 2, 'stretch' => 'some', 'cut_tags' => ['fitted'], 'g_chest_width' => 52, 'g_shoulder_width' => 43],
            ['name' => 'Grey Merino Crew', 'category' => 'top', 'subcategory' => 'sweater', 'primary_color_hex' => '#94a3b8', 'seasons' => ['autumn', 'winter'], 'formality' => 3, 'stretch' => 'some', 'cut_tags' => ['relaxed'], 'g_chest_width' => 55, 'g_shoulder_width' => 45],
            ['name' => 'Black Tee', 'category' => 'top', 'subcategory' => 'tshirt', 'primary_color_hex' => '#111827', 'seasons' => ['spring', 'summer', 'autumn'], 'formality' => 1, 'stretch' => 'some', 'cut_tags' => ['fitted'], 'g_chest_width' => 51, 'g_shoulder_width' => 42],
            ['name' => 'Linen Camp Shirt', 'category' => 'top', 'subcategory' => 'shirt', 'primary_color_hex' => '#fde68a', 'seasons' => ['summer'], 'formality' => 2, 'cut_tags' => ['relaxed', 'dropped_shoulder'], 'g_chest_width' => 56, 'g_shoulder_width' => 46],
            ['name' => 'Burgundy Knit', 'category' => 'top', 'subcategory' => 'sweater', 'primary_color_hex' => '#7f1d1d', 'seasons' => ['autumn', 'winter'], 'formality' => 3, 'stretch' => 'some', 'cut_tags' => ['fitted'], 'g_chest_width' => 53, 'g_shoulder_width' => 44],
            ['name' => 'Striped Breton', 'category' => 'top', 'subcategory' => 'tshirt', 'primary_color_hex' => '#1e40af', 'secondary_color_hex' => '#ffffff', 'pattern' => 'striped', 'seasons' => ['spring', 'summer'], 'formality' => 2, 'cut_tags' => ['fitted'], 'g_chest_width' => 52, 'g_shoulder_width' => 43],
            ['name' => 'Olive Overshirt', 'category' => 'top', 'subcategory' => 'shirt', 'primary_color_hex' => '#3f6212', 'seasons' => ['autumn', 'winter', 'spring'], 'formality' => 2, 'cut_tags' => ['relaxed', 'longline'], 'g_chest_width' => 57, 'g_shoulder_width' => 46],

            // Bottoms
            ['name' => 'Indigo Jeans', 'category' => 'bottom', 'subcategory' => 'jeans', 'primary_color_hex' => '#1e3a8a', 'seasons' => ['spring', 'summer', 'autumn', 'winter'], 'formality' => 2, 'stretch' => 'some', 'cut_tags' => ['straight', 'mid_waist'], 'g_waist_width' => 42, 'g_hip_width' => 52, 'g_inseam' => 80],
            ['name' => 'Stone Chinos', 'category' => 'bottom', 'subcategory' => 'chinos', 'primary_color_hex' => '#d6d3d1', 'seasons' => ['spring', 'summer', 'autumn'], 'formality' => 3, 'cut_tags' => ['slim_leg', 'mid_waist'], 'g_waist_width' => 41, 'g_hip_width' => 50, 'g_inseam' => 79],
            ['name' => 'Black Trousers', 'category' => 'bottom', 'subcategory' => 'trousers', 'primary_color_hex' => '#111827', 'seasons' => ['autumn', 'winter', 'spring'], 'formality' => 4, 'cut_tags' => ['straight', 'high_waist'], 'g_waist_width' => 41, 'g_hip_width' => 51, 'g_inseam' => 81],
            ['name' => 'Navy Shorts', 'category' => 'bottom', 'subcategory' => 'shorts', 'primary_color_hex' => '#1e3a8a', 'seasons' => ['summer'], 'formality' => 1, 'cut_tags' => ['relaxed', 'cropped'], 'g_waist_width' => 42, 'g_hip_width' => 52, 'g_inseam' => 25],
            ['name' => 'Olive Cargo', 'category' => 'bottom', 'subcategory' => 'trousers', 'primary_color_hex' => '#4d7c0f', 'seasons' => ['autumn', 'winter'], 'formality' => 2, 'cut_tags' => ['wide_leg', 'relaxed'], 'g_waist_width' => 43, 'g_hip_width' => 54, 'g_inseam' => 78],
            ['name' => 'Charcoal Wool Trouser', 'category' => 'bottom', 'subcategory' => 'trousers', 'primary_color_hex' => '#374151', 'seasons' => ['autumn', 'winter'], 'formality' => 5, 'cut_tags' => ['slim_leg', 'fitted'], 'g_waist_width' => 40, 'g_hip_width' => 50, 'g_inseam' => 80],
            ['name' => 'Cream Wide Pants', 'category' => 'bottom', 'subcategory' => 'trousers', 'primary_color_hex' => '#f5f5f4', 'seasons' => ['spring', 'summer'], 'formality' => 2, 'cut_tags' => ['wide_leg', 'high_waist'], 'g_waist_width' => 42, 'g_hip_width' => 53, 'g_inseam' => 79],

            // Dresses
            ['name' => 'Navy Shirt Dress', 'category' => 'dress', 'subcategory' => 'dress', 'primary_color_hex' => '#1e3a8a', 'seasons' => ['spring', 'summer'], 'formality' => 3, 'cut_tags' => ['a_line', 'wrap'], 'g_chest_width' => 52, 'g_waist_width' => 42, 'g_hip_width' => 54],
            ['name' => 'Black Cocktail Dress', 'category' => 'dress', 'subcategory' => 'dress', 'primary_color_hex' => '#111827', 'seasons' => ['autumn', 'winter'], 'formality' => 5, 'cut_tags' => ['fitted'], 'g_chest_width' => 50, 'g_waist_width' => 40, 'g_hip_width' => 52],
            ['name' => 'Floral Sundress', 'category' => 'dress', 'subcategory' => 'dress', 'primary_color_hex' => '#fb7185', 'pattern' => 'floral', 'seasons' => ['summer'], 'formality' => 2, 'cut_tags' => ['a_line', 'pleated'], 'g_chest_width' => 51, 'g_waist_width' => 41, 'g_hip_width' => 55],

            // Outerwear
            ['name' => 'Camel Overcoat', 'category' => 'outerwear', 'subcategory' => 'coat', 'primary_color_hex' => '#b45309', 'seasons' => ['autumn', 'winter'], 'formality' => 4, 'cut_tags' => ['longline', 'structured_shoulder'], 'g_chest_width' => 58, 'g_shoulder_width' => 46],
            ['name' => 'Black Bomber', 'category' => 'outerwear', 'subcategory' => 'jacket', 'primary_color_hex' => '#111827', 'seasons' => ['spring', 'autumn'], 'formality' => 2, 'cut_tags' => ['fitted'], 'g_chest_width' => 56, 'g_shoulder_width' => 45],
            ['name' => 'Denim Jacket', 'category' => 'outerwear', 'subcategory' => 'jacket', 'primary_color_hex' => '#1d4ed8', 'seasons' => ['spring', 'autumn'], 'formality' => 2, 'cut_tags' => ['structured_shoulder'], 'g_chest_width' => 55, 'g_shoulder_width' => 45],
            ['name' => 'Rain Shell', 'category' => 'outerwear', 'subcategory' => 'jacket', 'primary_color_hex' => '#0f172a', 'seasons' => ['spring', 'autumn', 'winter'], 'formality' => 2, 'cut_tags' => ['relaxed'], 'g_chest_width' => 58, 'g_shoulder_width' => 46],

            // Shoes
            ['name' => 'White Sneakers', 'category' => 'shoes', 'subcategory' => 'sneakers', 'primary_color_hex' => '#f8fafc', 'seasons' => ['spring', 'summer', 'autumn'], 'formality' => 2],
            ['name' => 'Brown Derbies', 'category' => 'shoes', 'subcategory' => 'shoes', 'primary_color_hex' => '#78350f', 'seasons' => ['autumn', 'winter', 'spring'], 'formality' => 4],
            ['name' => 'Black Chelsea Boots', 'category' => 'shoes', 'subcategory' => 'boots', 'primary_color_hex' => '#111827', 'seasons' => ['autumn', 'winter'], 'formality' => 3],
            ['name' => 'Sand Canvas Trainers', 'category' => 'shoes', 'subcategory' => 'sneakers', 'primary_color_hex' => '#e7e5e4', 'seasons' => ['summer'], 'formality' => 1],
            ['name' => 'Loafers', 'category' => 'shoes', 'subcategory' => 'shoes', 'primary_color_hex' => '#44403c', 'seasons' => ['spring', 'summer', 'autumn'], 'formality' => 3],

            // Accessories
            ['name' => 'Leather Belt', 'category' => 'accessory', 'subcategory' => 'belt', 'primary_color_hex' => '#78350f', 'seasons' => ['spring', 'summer', 'autumn', 'winter'], 'formality' => 3],
            ['name' => 'Navy Scarf', 'category' => 'accessory', 'subcategory' => 'scarf', 'primary_color_hex' => '#1e3a8a', 'seasons' => ['autumn', 'winter'], 'formality' => 3],
            ['name' => 'Wool Beanie', 'category' => 'accessory', 'subcategory' => 'hat', 'primary_color_hex' => '#334155', 'seasons' => ['winter'], 'formality' => 1],
            ['name' => 'Canvas Tote', 'category' => 'accessory', 'subcategory' => 'bag', 'primary_color_hex' => '#e7e5e4', 'seasons' => ['spring', 'summer'], 'formality' => 2],
        ];
    }
}
