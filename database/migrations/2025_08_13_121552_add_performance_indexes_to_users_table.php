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
        Schema::table('users', function (Blueprint $table) {
            // 複合索引用於搜尋功能優化
            $table->index(['username', 'is_active'], 'idx_users_username_status');
            $table->index(['name', 'is_active'], 'idx_users_name_status');
            $table->index(['email', 'is_active'], 'idx_users_email_status');
            
            // 複合索引用於角色篩選和狀態篩選
            $table->index(['is_active', 'deleted_at'], 'idx_users_active_deleted');
            
            // 最後登入時間索引（用於統計和排序）
            $table->index(['last_login_at'], 'idx_users_last_login');
            
            // 複合索引用於分頁優化
            $table->index(['created_at', 'id'], 'idx_users_created_id');
            $table->index(['updated_at', 'id'], 'idx_users_updated_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_username_status');
            $table->dropIndex('idx_users_name_status');
            $table->dropIndex('idx_users_email_status');
            $table->dropIndex('idx_users_active_deleted');
            $table->dropIndex('idx_users_last_login');
            $table->dropIndex('idx_users_created_id');
            $table->dropIndex('idx_users_updated_id');
        });
    }
};
