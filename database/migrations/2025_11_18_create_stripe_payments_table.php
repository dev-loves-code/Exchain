<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_payments', function (Blueprint $table) {
            $table->id('stripe_payment_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('stripe_charge_id', 100)->unique();
            $table->string('stripe_payment_method_id', 100)->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('USD');
            $table->enum('payment_type', ['card_recharge', 'bank_transfer', 'other']);
            $table->string('status', 50);
            $table->text('description')->nullable();
            $table->json('stripe_metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_payments');
    }
};
