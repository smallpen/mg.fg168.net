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
        // 為 permissions 表新增進階效能索引
        Schema::table('permissions', function (Blueprint $table) {
            // 檢查並新增 type 欄位（如果不存在）
            if (!Schema::hasColumn('permissions', 'type')) {
                $table->string('type')->default('view')->after('module')->comment('權限類型');
            }
            
            // 複合索引：類型 + 模組 + 名稱（用於類型篩選和排序）
            if (!$this->indexExists('permissions', 'permissions_type_module_name_idx')) {
                $table->index(['type', 'module', 'name'], 'permissions_type_module_name_idx');
            }
            
            // 複合索引：模組 + 類型（用於模組內類型統計）
            if (!$this->indexExists('permissions', 'permissions_module_type_idx')) {
                $table->index(['module', 'type'], 'permissions_module_type_idx');
            }
            
            // 名稱前綴索引（用於快速搜尋）
            if (!$this->indexExists('permissions', 'permissions_name_prefix_idx')) {
                $table->index([DB::raw('name(10)')], 'permissions_name_prefix_idx');
            }
            
            // 顯示名稱前綴索引（用於快速搜尋）
            if (!$this->indexExists('permissions', 'permissions_display_name_prefix_idx')) {
                $table->index([DB::raw('display_name(10)')], 'permissions_display_name_prefix_idx');
            }
        });

        // 為 role_permissions 表新增進階效能索引
        Schema::table('role_permissions', function (Blueprint $table) {
            // 複合索引：建立時間 + 角色 ID（用於時間範圍查詢）
            if (!$this->indexExists('role_permissions', 'role_perms_created_role_idx')) {
                $table->index(['created_at', 'role_id'], 'role_perms_created_role_idx');
            }
            
            // 複合索引：建立時間 + 權限 ID（用於權限使用歷史）
            if (!$this->indexExists('role_permissions', 'role_perms_created_perm_idx')) {
                $table->index(['created_at', 'permission_id'], 'role_perms_created_perm_idx');
            }
        });

        // 為 permission_dependencies 表新增進階效能索引
        Schema::table('permission_dependencies', function (Blueprint $table) {
            // 複合索引：依賴權限 + 權限（用於反向依賴查詢）
            if (!$this->indexExists('permission_dependencies', 'perm_dep_reverse_lookup_idx')) {
                $table->index(['depends_on_permission_id', 'permission_id'], 'perm_dep_reverse_lookup_idx');
            }
            
            // 複合索引：權限 + 依賴權限（用於正向依賴查詢）
            if (!$this->indexExists('permission_dependencies', 'perm_dep_forward_lookup_idx')) {
                $table->index(['permission_id', 'depends_on_permission_id'], 'perm_dep_forward_lookup_idx');
            }
        });

        // 為 user_roles 表新增進階效能索引（如果存在）
        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                // 複合索引：建立時間 + 使用者 ID（用於使用者角色歷史）
                if (!$this->indexExists('user_roles', 'user_roles_created_user_idx')) {
                    $table->index(['created_at', 'user_id'], 'user_roles_created_user_idx');
                }
                
                // 複合索引：建立時間 + 角色 ID（用於角色指派歷史）
                if (!$this->indexExists('user_roles', 'user_roles_created_role_idx')) {
                    $table->index(['created_at', 'role_id'], 'user_roles_created_role_idx');
                }
            });
        }

        // 為 permission_audit_logs 表新增進階效能索引（如果存在）
        if (Schema::hasTable('permission_audit_logs')) {
            Schema::table('permission_audit_logs', function (Blueprint $table) {
                // 複合索引：權限 ID + 動作 + 建立時間（用於權限操作歷史）
                if (!$this->indexExists('permission_audit_logs', 'perm_audit_perm_action_created_idx')) {
                    $table->index(['permission_id', 'action', 'created_at'], 'perm_audit_perm_action_created_idx');
                }
                
                // 複合索引：使用者 ID + 建立時間（用於使用者操作歷史）
                if (!$this->indexExists('permission_audit_logs', 'perm_audit_user_created_idx')) {
                    $table->index(['user_id', 'created_at'], 'perm_audit_user_created_idx');
                }
                
                // IP 地址索引（用於安全分析）
                if (!$this->indexExists('permission_audit_logs', 'perm_audit_ip_idx')) {
                    $table->index(['ip_address'], 'perm_audit_ip_idx');
                }
            });
        }

        // 建立權限使用統計的物化視圖（MySQL 8.0+）
        $this->createPermissionUsageView();
        
        // 建立權限依賴關係的物化視圖
        $this->createPermissionDependencyView();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 刪除物化視圖
        DB::statement('DROP VIEW IF EXISTS permission_usage_stats');
        DB::statement('DROP VIEW IF EXISTS permission_dependency_stats');

        // 移除 permissions 表的進階效能索引
        Schema::table('permissions', function (Blueprint $table) {
            if ($this->indexExists('permissions', 'permissions_type_module_name_idx')) {
                $table->dropIndex('permissions_type_module_name_idx');
            }
            if ($this->indexExists('permissions', 'permissions_module_type_idx')) {
                $table->dropIndex('permissions_module_type_idx');
            }
            if ($this->indexExists('permissions', 'permissions_name_prefix_idx')) {
                $table->dropIndex('permissions_name_prefix_idx');
            }
            if ($this->indexExists('permissions', 'permissions_display_name_prefix_idx')) {
                $table->dropIndex('permissions_display_name_prefix_idx');
            }
        });

        // 移除 role_permissions 表的進階效能索引
        Schema::table('role_permissions', function (Blueprint $table) {
            if ($this->indexExists('role_permissions', 'role_perms_created_role_idx')) {
                $table->dropIndex('role_perms_created_role_idx');
            }
            if ($this->indexExists('role_permissions', 'role_perms_created_perm_idx')) {
                $table->dropIndex('role_perms_created_perm_idx');
            }
        });

        // 移除 permission_dependencies 表的進階效能索引
        Schema::table('permission_dependencies', function (Blueprint $table) {
            if ($this->indexExists('permission_dependencies', 'perm_dep_reverse_lookup_idx')) {
                $table->dropIndex('perm_dep_reverse_lookup_idx');
            }
            if ($this->indexExists('permission_dependencies', 'perm_dep_forward_lookup_idx')) {
                $table->dropIndex('perm_dep_forward_lookup_idx');
            }
        });

        // 移除 user_roles 表的進階效能索引
        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                if ($this->indexExists('user_roles', 'user_roles_created_user_idx')) {
                    $table->dropIndex('user_roles_created_user_idx');
                }
                if ($this->indexExists('user_roles', 'user_roles_created_role_idx')) {
                    $table->dropIndex('user_roles_created_role_idx');
                }
            });
        }

        // 移除 permission_audit_logs 表的進階效能索引
        if (Schema::hasTable('permission_audit_logs')) {
            Schema::table('permission_audit_logs', function (Blueprint $table) {
                if ($this->indexExists('permission_audit_logs', 'perm_audit_perm_action_created_idx')) {
                    $table->dropIndex('perm_audit_perm_action_created_idx');
                }
                if ($this->indexExists('permission_audit_logs', 'perm_audit_user_created_idx')) {
                    $table->dropIndex('perm_audit_user_created_idx');
                }
                if ($this->indexExists('permission_audit_logs', 'perm_audit_ip_idx')) {
                    $table->dropIndex('perm_audit_ip_idx');
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
            return false;
        }
    }

    /**
     * 建立權限使用統計視圖
     */
    private function createPermissionUsageView(): void
    {
        try {
            DB::statement('
                CREATE OR REPLACE VIEW permission_usage_stats AS
                SELECT 
                    p.id,
                    p.name,
                    p.display_name,
                    p.module,
                    p.type,
                    COALESCE(role_count.count, 0) as role_count,
                    COALESCE(user_count.count, 0) as user_count,
                    COALESCE(dependency_count.count, 0) as dependency_count,
                    COALESCE(dependent_count.count, 0) as dependent_count,
                    CASE 
                        WHEN COALESCE(role_count.count, 0) > 0 THEN 1 
                        ELSE 0 
                    END as is_used,
                    CASE 
                        WHEN COALESCE(role_count.count, 0) >= 10 THEN "very_high"
                        WHEN COALESCE(role_count.count, 0) >= 5 THEN "high"
                        WHEN COALESCE(role_count.count, 0) >= 2 THEN "medium"
                        WHEN COALESCE(role_count.count, 0) >= 1 THEN "low"
                        ELSE "unused"
                    END as usage_level,
                    p.created_at,
                    p.updated_at
                FROM permissions p
                LEFT JOIN (
                    SELECT permission_id, COUNT(*) as count
                    FROM role_permissions
                    GROUP BY permission_id
                ) role_count ON p.id = role_count.permission_id
                LEFT JOIN (
                    SELECT rp.permission_id, COUNT(DISTINCT ur.user_id) as count
                    FROM role_permissions rp
                    JOIN user_roles ur ON rp.role_id = ur.role_id
                    GROUP BY rp.permission_id
                ) user_count ON p.id = user_count.permission_id
                LEFT JOIN (
                    SELECT permission_id, COUNT(*) as count
                    FROM permission_dependencies
                    GROUP BY permission_id
                ) dependency_count ON p.id = dependency_count.permission_id
                LEFT JOIN (
                    SELECT depends_on_permission_id, COUNT(*) as count
                    FROM permission_dependencies
                    GROUP BY depends_on_permission_id
                ) dependent_count ON p.id = dependent_count.depends_on_permission_id
            ');
        } catch (\Exception $e) {
            // 如果建立視圖失敗，記錄錯誤但不中斷遷移
            \Log::warning('無法建立權限使用統計視圖: ' . $e->getMessage());
        }
    }

    /**
     * 建立權限依賴關係統計視圖
     */
    private function createPermissionDependencyView(): void
    {
        try {
            DB::statement('
                CREATE OR REPLACE VIEW permission_dependency_stats AS
                SELECT 
                    p.id,
                    p.name,
                    p.display_name,
                    p.module,
                    p.type,
                    COALESCE(direct_deps.count, 0) as direct_dependencies,
                    COALESCE(direct_dependents.count, 0) as direct_dependents,
                    CASE 
                        WHEN COALESCE(direct_deps.count, 0) > 0 AND COALESCE(direct_dependents.count, 0) > 0 THEN "bridge"
                        WHEN COALESCE(direct_deps.count, 0) > 0 THEN "dependent"
                        WHEN COALESCE(direct_dependents.count, 0) > 0 THEN "dependency"
                        ELSE "isolated"
                    END as dependency_type,
                    p.created_at,
                    p.updated_at
                FROM permissions p
                LEFT JOIN (
                    SELECT permission_id, COUNT(*) as count
                    FROM permission_dependencies
                    GROUP BY permission_id
                ) direct_deps ON p.id = direct_deps.permission_id
                LEFT JOIN (
                    SELECT depends_on_permission_id, COUNT(*) as count
                    FROM permission_dependencies
                    GROUP BY depends_on_permission_id
                ) direct_dependents ON p.id = direct_dependents.depends_on_permission_id
            ');
        } catch (\Exception $e) {
            // 如果建立視圖失敗，記錄錯誤但不中斷遷移
            \Log::warning('無法建立權限依賴關係統計視圖: ' . $e->getMessage());
        }
    }
};