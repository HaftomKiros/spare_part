<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            // Rename selling_price → selling_price_max (default/max price)
            $table->renameColumn('selling_price', 'selling_price_max');
            // Add min price after buying_price
            $table->decimal('selling_price_min', 12, 2)->default(0)->after('buying_price');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->renameColumn('selling_price_max', 'selling_price');
            $table->dropColumn('selling_price_min');
        });
    }
};
