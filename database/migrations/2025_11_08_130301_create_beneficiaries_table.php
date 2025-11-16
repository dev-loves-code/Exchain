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

            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('email', 150)->nullable();

            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')
                ->references('payment_method_id')
                ->on('payment_methods')
                ->nullOnDelete();


            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->foreign('wallet_id')
                ->references('wallet_id')
                ->on('wallets')
                ->nullOnDelete();

            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->foreign('bank_account_id')
                ->references('bank_account_id')
                ->on('bank_accounts')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
