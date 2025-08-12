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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 通知類型：security, system, user_action, report
            $table->string('title'); // 通知標題
            $table->text('message'); // 通知內容
            $table->json('data')->nullable(); // 額外資料（如相關 ID、URL 等）
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal'); // 優先級
            $table->timestamp('read_at')->nullable(); // 已讀時間
            $table->boolean('is_browser_notification')->default(false); // 是否顯示瀏覽器通知
            $table->string('icon')->nullable(); // 通知圖示
            $table->string('color')->nullable(); // 通知顏色標識
            $table->string('action_url')->nullable(); // 點擊後導航的 URL
            $table->timestamps();
            
            // 索引優化
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'priority']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
