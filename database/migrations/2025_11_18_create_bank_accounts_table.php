<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id('bank_account_id');
            $table->string('holder_name', 150);
            $table->string('account_number', 100);
            $table->string('iban',100)->nullable();
            $table->string('country', 100);
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->string('routing_number', 50)->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->string('bank_name', 200);
            $table->string('stripe_bank_account_token', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'inactive', 'pending_verification'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
