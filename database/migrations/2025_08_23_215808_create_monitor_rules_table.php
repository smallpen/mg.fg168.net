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
        Schema::create('monitor_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 規則名稱
            $table->text('description'); // 規則描述
            $table->json('conditions'); // 觸發條件（JSON 格式）
            $table->json('actions'); // 執行動作（JSON 格式）
            $table->boolean('is_active')->default(true); // 是否啟用
            $table->unsignedBigInteger('created_by'); // 建立者 ID
            $table->tinyInteger('priority')->default(2); // 優先級：1-4
            $table->integer('threshold')->default(5); // 觸發閾值
            $table->integer('time_window')->default(3600); // 時間窗口（秒）
            $table->integer('cooldown_period')->default(300); // 冷卻期間（秒）
            $table->timestamp('last_triggered_at')->nullable(); // 最後觸發時間
            $table->timestamps();

            // 索引
            $table->index(['is_active', 'priority']);
            $table->index('created_by');
            $table->index('priority');
            $table->index('last_triggered_at');
            $table->index('created_at');

            // 外鍵約束
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_rules');
    }
};
