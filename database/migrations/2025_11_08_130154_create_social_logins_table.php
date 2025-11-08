<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_logins', function (Blueprint $table) {
            $table->id('social_login_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('provider', 50);
            $table->string('provider_user_id', 255);
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamps();
            
            $table->unique(['provider', 'provider_user_id'], 'unique_provider_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_logins');
    }
};