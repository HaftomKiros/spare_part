<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // STK-001
            $table->string('name');                  // Stock Mekelle
            $table->string('city')->nullable();      // Mekelle
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('manager')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Per-warehouse stock for spare parts
        Schema::create('warehouse_spare_part_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('spare_part_id')->constrained('spare_parts')->cascadeOnDelete();
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->timestamps();
            $table->unique(['warehouse_id', 'spare_part_id']);
        });

        // Per-warehouse stock for vehicles
        Schema::create('warehouse_vehicle_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->constrained('vehicle_models')->cascadeOnDelete();
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->default(2);
            $table->timestamps();
            $table->unique(['warehouse_id', 'vehicle_model_id']);
        });

        // Add warehouse_id to sales, purchases, stock_movements, stock_adjustments
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', fn($t) => $t->dropForeignIdFor(\App\Models\Warehouse::class));
        Schema::table('stock_movements',   fn($t) => $t->dropForeignIdFor(\App\Models\Warehouse::class));
        Schema::table('purchases',         fn($t) => $t->dropForeignIdFor(\App\Models\Warehouse::class));
        Schema::table('sales',             fn($t) => $t->dropForeignIdFor(\App\Models\Warehouse::class));
        Schema::dropIfExists('warehouse_vehicle_stock');
        Schema::dropIfExists('warehouse_spare_part_stock');
        Schema::dropIfExists('warehouses');
    }
};
