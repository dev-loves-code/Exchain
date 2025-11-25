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
        // Drop base_fee if it exists
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'base_fee')) {
                $table->dropColumn('base_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add it back only if it's not there
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'base_fee')) {
                $table->decimal('base_fee', 18, 2)->default(0);
            }
        });
    }
};
