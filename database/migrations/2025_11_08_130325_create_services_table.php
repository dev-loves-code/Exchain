<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id('service_id');
            $table->enum('service_type', ['transfer', 'payment', 'cash_out']);
            $table->enum('transfer_speed', ['instant', 'same_day', '1-3_days']);
            $table->decimal('base_fee', 18, 2)->default(0);
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};