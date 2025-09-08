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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->nullable()->comment('代理ID (可為空)');
            $table->unsignedBigInteger('player_id')->nullable()->comment('玩家ID (可為空)');
            $table->enum('type', [
                'agent_allocation',
                'agent_recovery', 
                'player_allocation',
                'player_recovery',
                'player_consumption',
                'system_adjustment'
            ])->comment('交易類型');
            $table->decimal('amount', 15, 2)->comment('交易金額');
            $table->decimal('balance_before', 15, 2)->comment('交易前餘額');
            $table->decimal('balance_after', 15, 2)->comment('交易後餘額');
            $table->string('description')->comment('交易描述');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('參考ID');
            $table->unsignedBigInteger('created_by')->nullable()->comment('操作者');
            $table->timestamps();

            // 索引
            $table->index(['agent_id', 'type']);
            $table->index(['player_id', 'type']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_id']);
            $table->index(['created_by']);
            $table->index(['created_at']);

            // 外鍵約束
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
