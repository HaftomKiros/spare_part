<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the purchase_type enum to include 'adjustment'
        DB::statement("ALTER TABLE purchases MODIFY COLUMN purchase_type ENUM('purchase','transfer','adjustment') NOT NULL DEFAULT 'purchase'");
    }

    public function down(): void
    {
        // Revert — first update any adjustment rows back to 'purchase'
        DB::statement("UPDATE purchases SET purchase_type = 'purchase' WHERE purchase_type = 'adjustment'");
        DB::statement("ALTER TABLE purchases MODIFY COLUMN purchase_type ENUM('purchase','transfer') NOT NULL DEFAULT 'purchase'");
    }
};
