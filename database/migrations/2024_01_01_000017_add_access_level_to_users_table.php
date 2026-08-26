<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Access level controls the user_id scoping behaviour:
            //   regular      → sees only own transactions in assigned warehouses
            //   manager      → sees ALL transactions in assigned warehouses (no user_id filter)
            //   super_admin  → sees ALL transactions in ALL warehouses (no filters)
            $table->enum('access_level', ['regular', 'manager', 'super_admin'])
                  ->default('regular')
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_level');
        });
    }
};
