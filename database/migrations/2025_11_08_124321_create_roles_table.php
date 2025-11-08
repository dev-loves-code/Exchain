<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_name', 50)->unique();
        });

        DB::table('roles')->insert([
            ['role_name' => 'admin'],
            ['role_name' => 'user'],
            ['role_name' => 'agent'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};