<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id('beneficiary_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->string('bank_account_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wallet_id')->references('wallet_id')->on('wallets')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
