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
        Schema::create('user_keyboard_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shortcut_key', 100)->comment('快捷鍵組合，例如：ctrl+shift+k');
            $table->string('action', 100)->comment('動作類型');
            $table->string('target', 255)->nullable()->comment('目標網址或參數');
            $table->string('description', 255)->comment('快捷鍵說明');
            $table->string('category', 50)->default('custom')->comment('分類');
            $table->boolean('enabled')->default(true)->comment('是否啟用');
            $table->timestamps();
            
            // 建立複合唯一索引，確保每個使用者的快捷鍵不重複
            $table->unique(['user_id', 'shortcut_key']);
            
            // 建立索引以提升查詢效能
            $table->index(['user_id', 'enabled']);
            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_keyboard_shortcuts');
    }
};
