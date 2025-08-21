<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 執行遷移 - 優化角色管理相關索引
     */
    public function up(): void
    {
        // 優化 roles 表索引
        Schema::table('roles', function (Blueprint $table) {
            // 檢查並建立索引（避免重複建立）
            if (!$this->indexExists('roles', 'roles_created_at_idx')) {
                $table->index(['created_at'], 'roles_created_at_idx');
            }
            
            // 複合索引用於常見的篩選組合
            if (!$this->indexExists('roles', 'roles_active_system_idx')) {
                $table->index(['is_active', 'is_system_role'], 'roles_active_system_idx');
            }
            if (!$this->indexExists('roles', 'roles_parent_active_idx')) {
                $table->index(['parent_id', 'is_active'], 'roles_parent_active_idx');
            }
        });

        // 優化 permissions 表索引
        Schema::table('permissions', function (Blueprint $table) {
            // 為模組欄位建立索引（常用於分組查詢）
            if (!$this->indexExists('permissions', 'permissions_module_idx')) {
                $table->index(['module'], 'permissions_module_idx');
            }
            if (!$this->indexExists('permissions', 'permissions_module_name_idx')) {
                $table->index(['module', 'name'], 'permissions_module_name_idx');
            }
            if (!$this->indexExists('permissions', 'permissions_created_at_idx')) {
                $table->index(['created_at'], 'permissions_created_at_idx');
            }
        });

        // 優化 role_permissions 表索引
        Schema::table('role_permissions', function (Blueprint $table) {
            // 為常用查詢建立索引
            if (!$this->indexExists('role_permissions', 'role_perms_role_created_idx')) {
                $table->index(['role_id', 'created_at'], 'role_perms_role_created_idx');
            }
            if (!$this->indexExists('role_permissions', 'role_perms_perm_created_idx')) {
                $table->index(['permission_id', 'created_at'], 'role_perms_perm_created_idx');
            }
        });

        // 優化 user_roles 表索引
        Schema::table('user_roles', function (Blueprint $table) {
            // 為常用查詢建立索引
            if (!$this->indexExists('user_roles', 'user_roles_user_created_idx')) {
                $table->index(['user_id', 'created_at'], 'user_roles_user_created_idx');
            }
            if (!$this->indexExists('user_roles', 'user_roles_role_created_idx')) {
                $table->index(['role_id', 'created_at'], 'user_roles_role_created_idx');
            }
        });
    }

    /**
     * 檢查索引是否存在
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        // 移除 roles 表索引
        Schema::table('roles', function (Blueprint $table) {
            if ($this->indexExists('roles', 'roles_created_at_idx')) {
                $table->dropIndex('roles_created_at_idx');
            }
            if ($this->indexExists('roles', 'roles_active_system_idx')) {
                $table->dropIndex('roles_active_system_idx');
            }
            if ($this->indexExists('roles', 'roles_parent_active_idx')) {
                $table->dropIndex('roles_parent_active_idx');
            }
        });

        // 移除 permissions 表索引
        Schema::table('permissions', function (Blueprint $table) {
            if ($this->indexExists('permissions', 'permissions_module_idx')) {
                $table->dropIndex('permissions_module_idx');
            }
            if ($this->indexExists('permissions', 'permissions_module_name_idx')) {
                $table->dropIndex('permissions_module_name_idx');
            }
            if ($this->indexExists('permissions', 'permissions_created_at_idx')) {
                $table->dropIndex('permissions_created_at_idx');
            }
        });

        // 移除 role_permissions 表索引
        Schema::table('role_permissions', function (Blueprint $table) {
            if ($this->indexExists('role_permissions', 'role_perms_role_created_idx')) {
                $table->dropIndex('role_perms_role_created_idx');
            }
            if ($this->indexExists('role_permissions', 'role_perms_perm_created_idx')) {
                $table->dropIndex('role_perms_perm_created_idx');
            }
        });

        // 移除 user_roles 表索引
        Schema::table('user_roles', function (Blueprint $table) {
            if ($this->indexExists('user_roles', 'user_roles_user_created_idx')) {
                $table->dropIndex('user_roles_user_created_idx');
            }
            if ($this->indexExists('user_roles', 'user_roles_role_created_idx')) {
                $table->dropIndex('user_roles_role_created_idx');
            }
        });
    }
};
