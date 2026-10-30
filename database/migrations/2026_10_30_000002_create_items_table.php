<?php

use App\Enums\ItemCategory;
use App\Enums\ItemFitType;
use App\Enums\ItemPattern;
use App\Enums\ItemStretch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('category', ItemCategory::values());
            $table->string('subcategory'); // e.g. tshirt, jeans
            $table->string('primary_color_hex');
            $table->string('secondary_color_hex')->nullable();
            $table->string('color_family'); // derived from primary_color_hex, used for filtering
            $table->enum('pattern', ItemPattern::values());
            $table->string('material')->nullable();
            $table->json('seasons'); // JSON array: spring, summer, autumn, winter
            $table->unsignedTinyInteger('formality'); // 1 to 5: 1 loungewear, 3 smart casual, 5 formal
            $table->enum('fit_type', ItemFitType::values());
            $table->enum('stretch', ItemStretch::values());
            $table->json('cut_tags')->nullable(); // JSON array: e.g. structured_shoulder, high_waist, a_line, etc.
            $table->string('size_label')->nullable();

            // Garment flat measurements in cm (all nullable)
            $table->decimal('g_chest_width', 5, 1)->nullable();
            $table->decimal('g_waist_width', 5, 1)->nullable();
            $table->decimal('g_hip_width', 5, 1)->nullable();
            $table->decimal('g_length', 5, 1)->nullable();
            $table->decimal('g_inseam', 5, 1)->nullable();
            $table->decimal('g_shoulder_width', 5, 1)->nullable();

            $table->string('photo_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
