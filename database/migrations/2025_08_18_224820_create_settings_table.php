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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('設定鍵值');
            $table->json('value')->nullable()->comment('設定值（JSON 格式）');
            $table->string('category', 50)->index()->comment('設定分類');
            $table->string('type', 30)->default('text')->comment('設定類型');
            $table->json('options')->nullable()->comment('設定選項（JSON 格式）');
            $table->text('description')->nullable()->comment('設定描述');
            $table->json('default_value')->nullable()->comment('預設值（JSON 格式）');
            $table->boolean('is_encrypted')->default(false)->comment('是否加密儲存');
            $table->boolean('is_system')->default(false)->comment('是否為系統設定');
            $table->boolean('is_public')->default(false)->comment('是否為公開設定');
            $table->integer('sort_order')->default(0)->comment('排序順序');
            $table->timestamps();
            
            // 索引優化
            $table->index(['category', 'sort_order'], 'idx_settings_category_sort');
            $table->index(['type', 'category'], 'idx_settings_type_category');
            $table->index(['is_system', 'is_public'], 'idx_settings_system_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
