<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. stock_transfers — dedicated transfer header table ──────────
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();      // TRF-2026-0001
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();
        });

        // ── 2. purchases — add purchase_type column ───────────────────────
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('purchase_type', ['purchase', 'transfer'])
                  ->default('purchase')
                  ->after('notes');
            // Link transfer-stub purchases back to the stock_transfer record
            $table->foreignId('stock_transfer_id')
                  ->nullable()
                  ->constrained('stock_transfers')
                  ->nullOnDelete()
                  ->after('purchase_type');
        });

        // ── 3. purchase_items — add transfer tracking columns ─────────────
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->boolean('is_transfer')->default(false)->after('total_sold');
            $table->foreignId('source_purchase_item_id')
                  ->nullable()
                  ->constrained('purchase_items')
                  ->nullOnDelete()
                  ->after('is_transfer');
        });

        // ── 4. stock_movements — extend enum with transfer_in/transfer_out ─
        // MySQL requires ALTER TABLE MODIFY to extend an enum.
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
            'purchase',
            'sale',
            'return_in',
            'return_out',
            'adjustment_in',
            'adjustment_out',
            'opening',
            'transfer_in',
            'transfer_out'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Reverse enum extension — remove transfer types
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN movement_type ENUM(
            'purchase',
            'sale',
            'return_in',
            'return_out',
            'adjustment_in',
            'adjustment_out',
            'opening'
        ) NOT NULL");

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['source_purchase_item_id']);
            $table->dropColumn(['is_transfer', 'source_purchase_item_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['stock_transfer_id']);
            $table->dropColumn(['purchase_type', 'stock_transfer_id']);
        });

        Schema::dropIfExists('stock_transfers');
    }
};
