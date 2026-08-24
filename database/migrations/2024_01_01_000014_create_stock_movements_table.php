<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vehicle stock tracking
        Schema::create('vehicle_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained('vehicle_models')->cascadeOnDelete();
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->default(2);
            $table->timestamps();
        });

        // Universal stock movement ledger
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('item_type', ['vehicle', 'spare_part']);
            $table->foreignId('vehicle_model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->foreignId('spare_part_id')->nullable()->constrained('spare_parts')->nullOnDelete();
            $table->enum('movement_type', [
                'purchase',       // Stock in from purchase
                'sale',           // Stock out from sale
                'return_in',      // Stock in from sale return
                'return_out',     // Stock out from purchase return
                'adjustment_in',  // Manual increase
                'adjustment_out', // Manual decrease
                'opening',        // Opening stock
            ]);
            $table->integer('quantity');              // Always positive
            $table->integer('quantity_before');       // Stock level before this move
            $table->integer('quantity_after');        // Stock level after this move
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('reference_type')->nullable(); // App\Models\Purchase, Sale, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Stock adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique(); // ADJ-2024-0001
            $table->foreignId('user_id')->constrained('users');
            $table->date('adjustment_date');
            $table->enum('adjustment_type', ['increase', 'decrease', 'recount']);
            $table->text('reason');
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('approved');
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->enum('item_type', ['vehicle', 'spare_part']);
            $table->foreignId('vehicle_model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->foreignId('spare_part_id')->nullable()->constrained('spare_parts')->nullOnDelete();
            $table->integer('quantity_before');
            $table->integer('quantity_adjusted'); // + or - depending on type
            $table->integer('quantity_after');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('vehicle_stocks');
    }
};
