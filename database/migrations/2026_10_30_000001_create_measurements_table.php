<?php

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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('measured_on');
            $table->decimal('height_cm', 5, 1);
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('neck_cm', 5, 1)->nullable();
            $table->decimal('shoulder_width_cm', 5, 1)->nullable(); // across the back
            $table->decimal('chest_cm', 5, 1); // bust for those who prefer
            $table->decimal('waist_cm', 5, 1);
            $table->decimal('hips_cm', 5, 1);
            $table->decimal('inseam_cm', 5, 1)->nullable();
            $table->decimal('arm_length_cm', 5, 1)->nullable();
            $table->decimal('thigh_cm', 5, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
