<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('sender_wallet_id');
            $table->unsignedBigInteger('receiver_wallet_id')->nullable();
            $table->string('receiver_bank_account', 100)->nullable();
            $table->string('receiver_email', 150)->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->foreignId('service_id')->constrained('services', 'service_id')->onDelete('restrict');
            $table->decimal('transfer_amount', 18, 2);
            $table->decimal('transfer_fee', 18, 2);
            $table->decimal('received_amount', 18, 2);
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->enum('status', ['pending', 'rejected', 'done', 'refunded'])->default('pending');
            $table->timestamps();

            $table->foreign('sender_wallet_id')->references('wallet_id')->on('wallets')->onDelete('restrict');
            $table->foreign('receiver_wallet_id')->references('wallet_id')->on('wallets')->onDelete('restrict');
            $table->foreign('agent_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
