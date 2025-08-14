<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行遷移 - 新增效能優化索引
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 搜尋優化索引
            $table->index(['name'], 'idx_users_name');
            $table->index(['email'], 'idx_users_email');
            
            // 狀態篩選索引
            $table->index(['is_active'], 'idx_users_status');
            
            // 建立時間排序索引
            $table->index(['created_at'], 'idx_users_created_at');
            
            // 複合索引用於常見查詢
            $table->index(['is_active', 'created_at'], 'idx_users_status_created');
            
            // 軟刪除索引
            $table->index(['deleted_at'], 'idx_users_deleted_at');
        });
    }

    /**
     * 回滾遷移 - 移除效能優化索引
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_created_at');
            $table->dropIndex('idx_users_status_created');
            $table->dropIndex('idx_users_deleted_at');
        });
    }
};
