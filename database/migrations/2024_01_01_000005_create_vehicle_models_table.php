<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->cascadeOnDelete();
            $table->string('brand')->default('Bajaj'); // Bajaj (extendable)
            $table->string('model_name');              // e.g. Boxer, Pulsar, RE, Maxima
            $table->string('model_code')->nullable();  // e.g. BX100
            $table->year('year')->nullable();
            $table->string('engine_cc')->nullable();   // Engine displacement
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('buying_price', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
