<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 角色管理整合服務
 * 
 * 整合所有角色管理功能，提供統一的服務介面
 */
class RoleManagementIntegrationService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
        private RoleCacheService $cacheService
    ) {}

    /**
     * 初始化角色管理系統
     * 
     * @return array
     */
    public function initializeSystem(): array
    {
        try {
            Log::info('開始初始化角色管理系統');

            // 檢查系統完整性
            $integrityCheck = $this->performSystemIntegrityCheck();
            
            if (!$integrityCheck['success']) {
                throw new \Exception('系統完整性檢查失敗: ' . implode(', ', $integrityCheck['errors']));
            }

            // 預熱快取
            $this->cacheService->warmupCache();

            // 驗證權限繼承
            $this->validatePermissionInheritance();

            // 檢查角色層級一致性
            $this->validateRoleHierarchy();

            Log::info('角色管理系統初始化完成');

            return [
                'success' => true,
                'message' => '角色管理系統初始化成功',
                'stats' => $this->getSystemStats()
            ];

        } catch (\Exception $e) {
            Log::error('角色管理系統初始化失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '系統初始化失敗: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * 執行系統完整性檢查
     * 
     * @return array
     */
    public function performSystemIntegrityCheck(): array
    {
        $errors = [];
        $warnings = [];

        try {
            // 檢查必要的資料表
            $requiredTables = ['roles', 'permissions', 'role_permissions', 'user_roles'];
            foreach ($requiredTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $errors[] = "缺少必要的資料表: {$table}";
                }
            }

            // 檢查系統角色
            $systemRoles = Role::where('is_system_role', true)->count();
            if ($systemRoles === 0) {
                $warnings[] = '沒有找到系統預設角色';
            }

            // 檢查權限完整性
            $orphanedPermissions = Permission::whereDoesntHave('roles')->count();
            if ($orphanedPermissions > 0) {
                $warnings[] = "發現 {$orphanedPermissions} 個未分配的權限";
            }

            // 檢查角色層級循環依賴
            $circularDependencies = $this->detectCircularDependencies();
            if (!empty($circularDependencies)) {
                $errors[] = '發現角色層級循環依賴: ' . implode(', ', $circularDependencies);
            }

            // 檢查權限依賴完整性
            $permissionDependencyIssues = $this->validatePermissionDependencies();
            if (!empty($permissionDependencyIssues)) {
                $warnings = array_merge($warnings, $permissionDependencyIssues);
            }

            return [
                'success' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
                'stats' => [
                    'total_roles' => Role::count(),
                    'active_roles' => Role::where('is_active', true)->count(),
                    'system_roles' => $systemRoles,
                    'total_permissions' => Permission::count(),
                    'orphaned_permissions' => $orphanedPermissions
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['系統檢查過程中發生錯誤: ' . $e->getMessage()],
                'warnings' => $warnings
            ];
        }
    }

    /**
     * 驗證權限繼承邏輯
     * 
     * @return bool
     */
    public function validatePermissionInheritance(): bool
    {
        try {
            $rolesWithParents = Role::whereNotNull('parent_id')->with(['parent', 'permissions'])->get();

            foreach ($rolesWithParents as $role) {
                $directPermissions = $role->permissions;
                $inheritedPermissions = $role->getInheritedPermissions();
                $allPermissions = $role->getAllPermissions();

                // 驗證總權限數量 = 直接權限 + 繼承權限（去重）
                $expectedCount = $directPermissions->merge($inheritedPermissions)->unique('id')->count();
                
                if ($allPermissions->count() !== $expectedCount) {
                    Log::warning("角色權限繼承計算異常", [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'direct_count' => $directPermissions->count(),
                        'inherited_count' => $inheritedPermissions->count(),
                        'all_count' => $allPermissions->count(),
                        'expected_count' => $expectedCount
                    ]);
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('權限繼承驗證失敗', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 驗證角色層級結構
     * 
     * @return bool
     */
    public function validateRoleHierarchy(): bool
    {
        try {
            $roles = Role::all();

            foreach ($roles as $role) {
                // 檢查深度是否合理（避免過深的層級）
                $depth = $role->getDepth();
                if ($depth > 10) {
                    Log::warning("角色層級過深", [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'depth' => $depth
                    ]);
                }

                // 檢查是否有循環依賴
                if ($role->parent_id && $role->hasCircularDependency($role->parent_id)) {
                    Log::error("發現角色循環依賴", [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'parent_id' => $role->parent_id
                    ]);
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('角色層級驗證失敗', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 取得系統統計資訊
     * 
     * @return array
     */
    public function getSystemStats(): array
    {
        return [
            'roles' => [
                'total' => Role::count(),
                'active' => Role::where('is_active', true)->count(),
                'system' => Role::where('is_system_role', true)->count(),
                'with_users' => Role::whereHas('users')->count(),
                'with_permissions' => Role::whereHas('permissions')->count(),
                'root_roles' => Role::whereNull('parent_id')->count(),
                'child_roles' => Role::whereNotNull('parent_id')->count()
            ],
            'permissions' => [
                'total' => Permission::count(),
                'by_module' => Permission::groupBy('module')->selectRaw('module, count(*) as count')->pluck('count', 'module')->toArray(),
                'assigned' => Permission::whereHas('roles')->count(),
                'unassigned' => Permission::whereDoesntHave('roles')->count()
            ],
            'users' => [
                'total' => User::count(),
                'with_roles' => User::whereHas('roles')->count(),
                'without_roles' => User::whereDoesntHave('roles')->count()
            ],
            'performance' => [
                'cache_stats' => $this->cacheService->getCacheStats(),
                'average_permissions_per_role' => Role::withCount('permissions')->get()->avg('permissions_count'),
                'average_roles_per_user' => User::withCount('roles')->get()->avg('roles_count')
            ]
        ];
    }

    /**
     * 執行效能優化
     * 
     * @return array
     */
    public function performOptimization(): array
    {
        try {
            $results = [];

            // 清理並重建快取
            $this->cacheService->clearAllCache();
            $this->cacheService->warmupCache();
            $results['cache_optimization'] = '快取已清理並重建';

            // 優化資料庫索引（如果需要）
            $this->optimizeDatabaseIndexes();
            $results['database_optimization'] = '資料庫索引已優化';

            // 清理無效的關聯
            $cleanupResults = $this->cleanupInvalidRelations();
            $results['cleanup'] = $cleanupResults;

            // 更新統計資訊
            $this->updateStatistics();
            $results['statistics'] = '統計資訊已更新';

            return [
                'success' => true,
                'message' => '系統優化完成',
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('系統優化失敗', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '系統優化失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 檢測循環依賴
     * 
     * @return array
     */
    private function detectCircularDependencies(): array
    {
        $issues = [];
        $roles = Role::whereNotNull('parent_id')->get();

        foreach ($roles as $role) {
            if ($role->hasCircularDependency($role->parent_id)) {
                $issues[] = "角色 {$role->name} (ID: {$role->id}) 與父角色形成循環依賴";
            }
        }

        return $issues;
    }

    /**
     * 驗證權限依賴關係
     * 
     * @return array
     */
    private function validatePermissionDependencies(): array
    {
        $issues = [];

        // 這裡可以添加權限依賴關係的驗證邏輯
        // 例如檢查權限依賴是否存在、是否形成循環等

        return $issues;
    }

    /**
     * 優化資料庫索引
     * 
     * @return void
     */
    private function optimizeDatabaseIndexes(): void
    {
        // 檢查並建立必要的索引
        $schema = DB::getSchemaBuilder();

        // 角色表索引
        if (!$this->indexExists('roles', 'roles_name_index')) {
            $schema->table('roles', function ($table) {
                $table->index('name');
            });
        }

        if (!$this->indexExists('roles', 'roles_parent_id_index')) {
            $schema->table('roles', function ($table) {
                $table->index('parent_id');
            });
        }

        // 權限表索引
        if (!$this->indexExists('permissions', 'permissions_module_index')) {
            $schema->table('permissions', function ($table) {
                $table->index('module');
            });
        }

        // 關聯表索引
        if (!$this->indexExists('role_permissions', 'role_permissions_role_id_index')) {
            $schema->table('role_permissions', function ($table) {
                $table->index('role_id');
            });
        }

        if (!$this->indexExists('user_roles', 'user_roles_user_id_index')) {
            $schema->table('user_roles', function ($table) {
                $table->index('user_id');
            });
        }
    }

    /**
     * 檢查索引是否存在
     * 
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $idx) {
                if ($idx->Key_name === $index) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清理無效的關聯
     * 
     * @return array
     */
    private function cleanupInvalidRelations(): array
    {
        $results = [];

        // 清理無效的角色權限關聯
        $invalidRolePermissions = DB::table('role_permissions')
            ->leftJoin('roles', 'role_permissions.role_id', '=', 'roles.id')
            ->leftJoin('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereNull('roles.id')
            ->orWhereNull('permissions.id')
            ->count();

        if ($invalidRolePermissions > 0) {
            DB::table('role_permissions')
                ->leftJoin('roles', 'role_permissions.role_id', '=', 'roles.id')
                ->leftJoin('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where(function ($query) {
                    $query->whereNull('roles.id')->orWhereNull('permissions.id');
                })
                ->delete();

            $results['invalid_role_permissions'] = "清理了 {$invalidRolePermissions} 個無效的角色權限關聯";
        }

        // 清理無效的使用者角色關聯
        $invalidUserRoles = DB::table('user_roles')
            ->leftJoin('users', 'user_roles.user_id', '=', 'users.id')
            ->leftJoin('roles', 'user_roles.role_id', '=', 'roles.id')
            ->whereNull('users.id')
            ->orWhereNull('roles.id')
            ->count();

        if ($invalidUserRoles > 0) {
            DB::table('user_roles')
                ->leftJoin('users', 'user_roles.user_id', '=', 'users.id')
                ->leftJoin('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where(function ($query) {
                    $query->whereNull('users.id')->orWhereNull('roles.id');
                })
                ->delete();

            $results['invalid_user_roles'] = "清理了 {$invalidUserRoles} 個無效的使用者角色關聯";
        }

        return $results;
    }

    /**
     * 更新統計資訊
     * 
     * @return void
     */
    private function updateStatistics(): void
    {
        // 更新角色統計快取
        $this->cacheService->clearRoleCache();
        $this->cacheService->getRoleStats();

        // 更新權限統計快取
        $this->cacheService->clearPermissionCache();
        $this->cacheService->getPermissionsByModule();
    }

    /**
     * 執行健康檢查
     * 
     * @return array
     */
    public function performHealthCheck(): array
    {
        $checks = [];

        // 檢查資料庫連線
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy', 'message' => '資料庫連線正常'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'unhealthy', 'message' => '資料庫連線失敗: ' . $e->getMessage()];
        }

        // 檢查快取系統
        try {
            Cache::put('health_check', 'test', 60);
            $value = Cache::get('health_check');
            if ($value === 'test') {
                $checks['cache'] = ['status' => 'healthy', 'message' => '快取系統正常'];
            } else {
                $checks['cache'] = ['status' => 'unhealthy', 'message' => '快取系統異常'];
            }
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'message' => '快取系統錯誤: ' . $e->getMessage()];
        }

        // 檢查角色系統完整性
        $integrityCheck = $this->performSystemIntegrityCheck();
        $checks['integrity'] = [
            'status' => $integrityCheck['success'] ? 'healthy' : 'unhealthy',
            'message' => $integrityCheck['success'] ? '系統完整性正常' : '系統完整性檢查失敗',
            'details' => $integrityCheck
        ];

        // 檢查權限繼承
        $inheritanceCheck = $this->validatePermissionInheritance();
        $checks['permission_inheritance'] = [
            'status' => $inheritanceCheck ? 'healthy' : 'unhealthy',
            'message' => $inheritanceCheck ? '權限繼承正常' : '權限繼承異常'
        ];

        // 檢查角色層級
        $hierarchyCheck = $this->validateRoleHierarchy();
        $checks['role_hierarchy'] = [
            'status' => $hierarchyCheck ? 'healthy' : 'unhealthy',
            'message' => $hierarchyCheck ? '角色層級正常' : '角色層級異常'
        ];

        $overallStatus = collect($checks)->every(function ($check) {
            return $check['status'] === 'healthy';
        }) ? 'healthy' : 'unhealthy';

        return [
            'overall_status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $checks
        ];
    }
}