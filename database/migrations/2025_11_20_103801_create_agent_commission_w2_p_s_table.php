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
        Schema::create('agent_commission_w2_p_s', function (Blueprint $table) {
            $table->bigIncrements('commission_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('agent_id');
            $table->decimal('commission_amount', 18, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_id', 'agent_commissions_w2p_transaction_id_foreign');
            $table->index('agent_id', 'agent_commissions_w2p_agent_id_foreign');

            $table->foreign('transaction_id')
                ->references('transaction_id')->on('transactions')
                ->onDelete('cascade');

            $table->foreign('agent_id')
                ->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_commission_w2_p_s');
    }
};
