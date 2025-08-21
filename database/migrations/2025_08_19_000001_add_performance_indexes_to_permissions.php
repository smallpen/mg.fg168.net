<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 為 permissions 表新增效能優化索引
        Schema::table('permissions', function (Blueprint $table) {
            // 複合索引：模組 + 類型 + 名稱（用於分組查詢和排序）
            if (!$this->indexExists('permissions', 'permissions_module_type_name_idx')) {
                $table->index(['module', 'type', 'name'], 'permissions_module_type_name_idx');
            }
            
            // 顯示名稱索引（用於搜尋）
            if (!$this->indexExists('permissions', 'permissions_display_name_idx')) {
                $table->index(['display_name'], 'permissions_display_name_idx');
            }
            
            // 更新時間索引（用於快取失效和同步）
            if (!$this->indexExists('permissions', 'permissions_updated_at_idx')) {
                $table->index(['updated_at'], 'permissions_updated_at_idx');
            }
            
            // 複合索引：模組 + 更新時間（用於模組級快取管理）
            if (!$this->indexExists('permissions', 'permissions_module_updated_idx')) {
                $table->index(['module', 'updated_at'], 'permissions_module_updated_idx');
            }
        });

        // 為 permission_dependencies 表新增效能優化索引
        Schema::table('permission_dependencies', function (Blueprint $table) {
            // 複合索引：依賴權限 + 建立時間（用於依賴關係查詢）
            if (!$this->indexExists('permission_dependencies', 'perm_dep_depends_created_idx')) {
                $table->index(['depends_on_permission_id', 'created_at'], 'perm_dep_depends_created_idx');
            }
            
            // 複合索引：權限 + 建立時間（用於被依賴查詢）
            if (!$this->indexExists('permission_dependencies', 'perm_dep_perm_created_idx')) {
                $table->index(['permission_id', 'created_at'], 'perm_dep_perm_created_idx');
            }
            
            // 更新時間索引（用於快取管理）
            if (!$this->indexExists('permission_dependencies', 'perm_dep_updated_at_idx')) {
                $table->index(['updated_at'], 'perm_dep_updated_at_idx');
            }
        });

        // 為 role_permissions 表新增額外的效能優化索引
        Schema::table('role_permissions', function (Blueprint $table) {
            // 複合索引：權限 + 角色（用於反向查詢）
            if (!$this->indexExists('role_permissions', 'role_perms_perm_role_idx')) {
                $table->index(['permission_id', 'role_id'], 'role_perms_perm_role_idx');
            }
            
            // 更新時間索引（用於快取管理）
            if (!$this->indexExists('role_permissions', 'role_perms_updated_at_idx')) {
                $table->index(['updated_at'], 'role_perms_updated_at_idx');
            }
        });

        // 為 user_roles 表新增效能優化索引（如果存在）
        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                // 複合索引：角色 + 使用者（用於反向查詢）
                if (!$this->indexExists('user_roles', 'user_roles_role_user_idx')) {
                    $table->index(['role_id', 'user_id'], 'user_roles_role_user_idx');
                }
                
                // 複合索引：使用者 + 建立時間（用於使用者權限歷史查詢）
                if (!$this->indexExists('user_roles', 'user_roles_user_created_idx')) {
                    $table->index(['user_id', 'created_at'], 'user_roles_user_created_idx');
                }
                
                // 複合索引：角色 + 建立時間（用於角色使用統計）
                if (!$this->indexExists('user_roles', 'user_roles_role_created_idx')) {
                    $table->index(['role_id', 'created_at'], 'user_roles_role_created_idx');
                }
            });
        }

        // 為 permission_audit_logs 表新增效能優化索引（如果存在）
        if (Schema::hasTable('permission_audit_logs')) {
            Schema::table('permission_audit_logs', function (Blueprint $table) {
                // 複合索引：權限模組 + 動作 + 建立時間（用於模組級審計查詢）
                if (!$this->indexExists('permission_audit_logs', 'perm_audit_module_action_created_idx')) {
                    $table->index(['permission_module', 'action', 'created_at'], 'perm_audit_module_action_created_idx');
                }
                
                // 複合索引：使用者名稱 + 建立時間（用於使用者操作歷史）
                if (!$this->indexExists('permission_audit_logs', 'perm_audit_username_created_idx')) {
                    $table->index(['username', 'created_at'], 'perm_audit_username_created_idx');
                }
                
                // 複合索引：權限名稱 + 動作（用於特定權限的操作查詢）
                if (!$this->indexExists('permission_audit_logs', 'perm_audit_name_action_idx')) {
                    $table->index(['permission_name', 'action'], 'perm_audit_name_action_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移除 permissions 表的效能優化索引
        Schema::table('permissions', function (Blueprint $table) {
            if ($this->indexExists('permissions', 'permissions_module_type_name_idx')) {
                $table->dropIndex('permissions_module_type_name_idx');
            }
            if ($this->indexExists('permissions', 'permissions_display_name_idx')) {
                $table->dropIndex('permissions_display_name_idx');
            }
            if ($this->indexExists('permissions', 'permissions_updated_at_idx')) {
                $table->dropIndex('permissions_updated_at_idx');
            }
            if ($this->indexExists('permissions', 'permissions_module_updated_idx')) {
                $table->dropIndex('permissions_module_updated_idx');
            }
        });

        // 移除 permission_dependencies 表的效能優化索引
        Schema::table('permission_dependencies', function (Blueprint $table) {
            if ($this->indexExists('permission_dependencies', 'perm_dep_depends_created_idx')) {
                $table->dropIndex('perm_dep_depends_created_idx');
            }
            if ($this->indexExists('permission_dependencies', 'perm_dep_perm_created_idx')) {
                $table->dropIndex('perm_dep_perm_created_idx');
            }
            if ($this->indexExists('permission_dependencies', 'perm_dep_updated_at_idx')) {
                $table->dropIndex('perm_dep_updated_at_idx');
            }
        });

        // 移除 role_permissions 表的效能優化索引
        Schema::table('role_permissions', function (Blueprint $table) {
            if ($this->indexExists('role_permissions', 'role_perms_perm_role_idx')) {
                $table->dropIndex('role_perms_perm_role_idx');
            }
            if ($this->indexExists('role_permissions', 'role_perms_updated_at_idx')) {
                $table->dropIndex('role_perms_updated_at_idx');
            }
        });

        // 移除 user_roles 表的效能優化索引
        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                if ($this->indexExists('user_roles', 'user_roles_role_user_idx')) {
                    $table->dropIndex('user_roles_role_user_idx');
                }
                if ($this->indexExists('user_roles', 'user_roles_user_created_idx')) {
                    $table->dropIndex('user_roles_user_created_idx');
                }
                if ($this->indexExists('user_roles', 'user_roles_role_created_idx')) {
                    $table->dropIndex('user_roles_role_created_idx');
                }
            });
        }

        // 移除 permission_audit_logs 表的效能優化索引
        if (Schema::hasTable('permission_audit_logs')) {
            Schema::table('permission_audit_logs', function (Blueprint $table) {
                if ($this->indexExists('permission_audit_logs', 'perm_audit_module_action_created_idx')) {
                    $table->dropIndex('perm_audit_module_action_created_idx');
                }
                if ($this->indexExists('permission_audit_logs', 'perm_audit_username_created_idx')) {
                    $table->dropIndex('perm_audit_username_created_idx');
                }
                if ($this->indexExists('permission_audit_logs', 'perm_audit_name_action_idx')) {
                    $table->dropIndex('perm_audit_name_action_idx');
                }
            });
        }
    }

    /**
     * 檢查索引是否存在
     *
     * @param string $table 資料表名稱
     * @param string $index 索引名稱
     * @return bool
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            // 如果無法查詢索引，假設索引不存在
            return false;
        }
    }
};