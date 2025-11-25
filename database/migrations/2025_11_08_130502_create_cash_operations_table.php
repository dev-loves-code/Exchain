<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_operations', function (Blueprint $table) {

            $table->id('cash_op_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('agent_id');
            $table->enum('operation_type', ['deposit', 'withdrawal']);
            
           
            $table->decimal('amount', 18, 2); 
            $table->string('currency_code', 10); 
            
           
            $table->decimal('wallet_amount', 18, 2); 
            
            
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->unsignedBigInteger('rate_id')->nullable();
            
            $table->decimal('agent_commission', 18, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                ->default('pending');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('wallet_id')
                ->references('wallet_id')
                ->on('wallets')
                ->onDelete('cascade');

            $table->foreign('agent_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('rate_id')
                ->references('rate_id')
                ->on('currency_rates')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_operations');
    }
};