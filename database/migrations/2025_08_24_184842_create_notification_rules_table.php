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
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 規則名稱
            $table->text('description')->nullable(); // 規則描述
            $table->json('conditions'); // 觸發條件（JSON 格式）
            $table->json('actions'); // 執行動作（JSON 格式）
            $table->boolean('is_active')->default(true); // 是否啟用
            $table->tinyInteger('priority')->default(2); // 優先級 (1=低, 2=一般, 3=高, 4=緊急)
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // 建立者
            $table->integer('triggered_count')->default(0); // 觸發次數
            $table->timestamp('last_triggered_at')->nullable(); // 最後觸發時間
            $table->timestamps();

            // 索引優化
            $table->index(['is_active', 'priority']);
            $table->index('created_by');
            $table->index('last_triggered_at');
            $table->index(['is_active', 'last_triggered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
