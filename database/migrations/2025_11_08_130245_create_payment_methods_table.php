<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('payment_method_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('method_type', 50); // card, bank_account
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 20)->nullable();
            $table->string('stripe_payment_method_id', 100)->nullable()->unique();
            $table->string('stripe_customer_id', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};