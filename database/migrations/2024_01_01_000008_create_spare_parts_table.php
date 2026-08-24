<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_category_id')->constrained('part_categories')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->string('part_number')->unique();    // Internal part code
            $table->string('oem_number')->nullable();   // Original manufacturer number
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('buying_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->integer('reorder_level')->default(5); // Low stock threshold
            $table->integer('current_stock')->default(0);
            $table->string('location')->nullable();     // Shelf/rack location
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Pivot: which vehicle models a spare part is compatible with
        Schema::create('spare_part_vehicle_model', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_id')->constrained('spare_parts')->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->constrained('vehicle_models')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_part_vehicle_model');
        Schema::dropIfExists('spare_parts');
    }
};
