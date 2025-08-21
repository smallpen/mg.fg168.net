<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 角色層級和權限繼承驗證服務
 * 
 * 驗證角色層級結構的完整性和權限繼承的正確性
 */
class RoleHierarchyValidationService
{
    private RoleCacheService $cacheService;
    private array $validationErrors = [];
    private array $validationWarnings = [];
    private array $validationResults = [];

    public function __construct(RoleCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 執行完整的角色層級和權限繼承驗證
     * 
     * @return array
     */
    public function performCompleteValidation(): array
    {
        try {
            Log::info('開始執行角色層級和權限繼承驗證');

            $this->validationErrors = [];
            $this->validationWarnings = [];
            $this->validationResults = [];

            // 驗證角色層級結構
            $this->validateRoleHierarchyStructure();

            // 驗證權限繼承邏輯
            $this->validatePermissionInheritanceLogic();

            // 驗證循環依賴
            $this->validateCircularDependencies();

            // 驗證層級深度
            $this->validateHierarchyDepth();

            // 驗證權限一致性
            $this->validatePermissionConsistency();

            // 驗證使用者權限計算
            $this->validateUserPermissionCalculation();

            // 驗證快取一致性
            $this->validateCacheConsistency();

            // 執行效能驗證
            $this->validatePerformanceImpact();

            // 生成驗證報告
            $validationReport = $this->generateValidationReport();

            Log::info('角色層級和權限繼承驗證完成', [
                'errors_count' => count($this->validationErrors),
                'warnings_count' => count($this->validationWarnings),
                'overall_status' => $validationReport['overall_status']
            ]);

            return [
                'success' => true,
                'timestamp' => now()->toISOString(),
                'validation_report' => $validationReport,
                'validation_results' => $this->validationResults,
                'errors' => $this->validationErrors,
                'warnings' => $this->validationWarnings
            ];

        } catch (\Exception $e) {
            Log::error('角色層級和權限繼承驗證失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->validationErrors,
                'warnings' => $this->validationWarnings
            ];
        }
    }

    /**
     * 驗證角色層級結構
     * 
     * @return void
     */
    private function validateRoleHierarchyStructure(): void
    {
        $roles = Role::with(['parent', 'children'])->get();
        $structureIssues = [];

        foreach ($roles as $role) {
            $issues = [];

            // 檢查父角色是否存在
            if ($role->parent_id && !$role->parent) {
                $issues[] = "父角色 ID {$role->parent_id} 不存在";
            }

            // 檢查父角色是否啟用
            if ($role->parent && !$role->parent->is_active) {
                $issues[] = "父角色 '{$role->parent->name}' 未啟用";
            }

            // 檢查子角色關聯
            $childrenIds = $role->children->pluck('id')->toArray();
            $actualChildrenIds = Role::where('parent_id', $role->id)->pluck('id')->toArray();
            
            if (array_diff($childrenIds, $actualChildrenIds) || array_diff($actualChildrenIds, $childrenIds)) {
                $issues[] = "子角色關聯不一致";
            }

            if (!empty($issues)) {
                $structureIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'issues' => $issues
                ];
            }
        }

        if (!empty($structureIssues)) {
            $this->validationErrors[] = [
                'type' => 'hierarchy_structure_issues',
                'message' => '角色層級結構存在問題',
                'details' => $structureIssues
            ];
        }

        $this->validationResults['hierarchy_structure'] = [
            'total_roles' => $roles->count(),
            'roles_with_issues' => count($structureIssues),
            'root_roles' => $roles->whereNull('parent_id')->count(),
            'child_roles' => $roles->whereNotNull('parent_id')->count()
        ];
    }

    /**
     * 驗證權限繼承邏輯
     * 
     * @return void
     */
    private function validatePermissionInheritanceLogic(): void
    {
        $rolesWithParents = Role::whereNotNull('parent_id')->with(['parent', 'permissions'])->get();
        $inheritanceIssues = [];

        foreach ($rolesWithParents as $role) {
            $issues = [];

            try {
                // 取得直接權限
                $directPermissions = $role->permissions;
                
                // 取得繼承權限
                $inheritedPermissions = $role->getInheritedPermissions();
                
                // 取得所有權限
                $allPermissions = $role->getAllPermissions();

                // 驗證權限數量邏輯
                $expectedTotalCount = $directPermissions->merge($inheritedPermissions)->unique('id')->count();
                if ($allPermissions->count() !== $expectedTotalCount) {
                    $issues[] = "權限總數計算錯誤: 預期 {$expectedTotalCount}, 實際 {$allPermissions->count()}";
                }

                // 驗證繼承權限是否包含在所有權限中
                foreach ($inheritedPermissions as $inheritedPermission) {
                    if (!$allPermissions->contains('id', $inheritedPermission->id)) {
                        $issues[] = "繼承權限 '{$inheritedPermission->name}' 未包含在所有權限中";
                    }
                }

                // 驗證直接權限是否包含在所有權限中
                foreach ($directPermissions as $directPermission) {
                    if (!$allPermissions->contains('id', $directPermission->id)) {
                        $issues[] = "直接權限 '{$directPermission->name}' 未包含在所有權限中";
                    }
                }

                // 驗證權限繼承的遞迴性
                if ($role->parent && $role->parent->parent) {
                    $grandparentPermissions = $role->parent->parent->getAllPermissions();
                    foreach ($grandparentPermissions as $grandparentPermission) {
                        if (!$allPermissions->contains('id', $grandparentPermission->id)) {
                            $issues[] = "祖父角色權限 '{$grandparentPermission->name}' 未正確繼承";
                        }
                    }
                }

            } catch (\Exception $e) {
                $issues[] = "權限繼承計算異常: " . $e->getMessage();
            }

            if (!empty($issues)) {
                $inheritanceIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'parent_role' => $role->parent ? $role->parent->name : null,
                    'issues' => $issues
                ];
            }
        }

        if (!empty($inheritanceIssues)) {
            $this->validationErrors[] = [
                'type' => 'permission_inheritance_issues',
                'message' => '權限繼承邏輯存在問題',
                'details' => $inheritanceIssues
            ];
        }

        $this->validationResults['permission_inheritance'] = [
            'roles_with_parents' => $rolesWithParents->count(),
            'roles_with_issues' => count($inheritanceIssues),
            'successful_inheritance_roles' => $rolesWithParents->count() - count($inheritanceIssues)
        ];
    }

    /**
     * 驗證循環依賴
     * 
     * @return void
     */
    private function validateCircularDependencies(): void
    {
        $roles = Role::whereNotNull('parent_id')->get();
        $circularDependencies = [];

        foreach ($roles as $role) {
            if ($this->hasCircularDependency($role)) {
                $circularDependencies[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'parent_id' => $role->parent_id,
                    'dependency_chain' => $this->getCircularDependencyChain($role)
                ];
            }
        }

        if (!empty($circularDependencies)) {
            $this->validationErrors[] = [
                'type' => 'circular_dependencies',
                'message' => '發現角色層級循環依賴',
                'details' => $circularDependencies
            ];
        }

        $this->validationResults['circular_dependencies'] = [
            'total_roles_checked' => $roles->count(),
            'circular_dependencies_found' => count($circularDependencies),
            'clean_roles' => $roles->count() - count($circularDependencies)
        ];
    }

    /**
     * 驗證層級深度
     * 
     * @return void
     */
    private function validateHierarchyDepth(): void
    {
        $roles = Role::all();
        $depthIssues = [];
        $maxRecommendedDepth = 5;
        $maxAllowedDepth = 10;

        foreach ($roles as $role) {
            $depth = $this->calculateRoleDepth($role);
            
            if ($depth > $maxAllowedDepth) {
                $depthIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'depth' => $depth,
                    'severity' => 'error',
                    'message' => "層級深度 {$depth} 超過最大允許深度 {$maxAllowedDepth}"
                ];
            } elseif ($depth > $maxRecommendedDepth) {
                $depthIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'depth' => $depth,
                    'severity' => 'warning',
                    'message' => "層級深度 {$depth} 超過建議深度 {$maxRecommendedDepth}"
                ];
            }
        }

        $errorIssues = collect($depthIssues)->where('severity', 'error');
        $warningIssues = collect($depthIssues)->where('severity', 'warning');

        if ($errorIssues->isNotEmpty()) {
            $this->validationErrors[] = [
                'type' => 'excessive_hierarchy_depth',
                'message' => '角色層級深度超過允許範圍',
                'details' => $errorIssues->toArray()
            ];
        }

        if ($warningIssues->isNotEmpty()) {
            $this->validationWarnings[] = [
                'type' => 'deep_hierarchy_warning',
                'message' => '角色層級深度較深，可能影響效能',
                'details' => $warningIssues->toArray()
            ];
        }

        $this->validationResults['hierarchy_depth'] = [
            'total_roles' => $roles->count(),
            'max_depth_found' => $roles->map(fn($role) => $this->calculateRoleDepth($role))->max(),
            'average_depth' => $roles->map(fn($role) => $this->calculateRoleDepth($role))->avg(),
            'roles_exceeding_recommended_depth' => $warningIssues->count(),
            'roles_exceeding_max_depth' => $errorIssues->count()
        ];
    }

    /**
     * 驗證權限一致性
     * 
     * @return void
     */
    private function validatePermissionConsistency(): void
    {
        $roles = Role::with('permissions')->get();
        $consistencyIssues = [];

        foreach ($roles as $role) {
            $issues = [];

            // 檢查權限是否存在
            $rolePermissionIds = $role->permissions->pluck('id')->toArray();
            $existingPermissionIds = Permission::whereIn('id', $rolePermissionIds)->pluck('id')->toArray();
            $missingPermissionIds = array_diff($rolePermissionIds, $existingPermissionIds);

            if (!empty($missingPermissionIds)) {
                $issues[] = "關聯的權限不存在: " . implode(', ', $missingPermissionIds);
            }

            // 檢查權限關聯表的一致性
            $dbRolePermissions = DB::table('role_permissions')
                                  ->where('role_id', $role->id)
                                  ->pluck('permission_id')
                                  ->toArray();

            $modelPermissions = $role->permissions->pluck('id')->toArray();

            if (array_diff($dbRolePermissions, $modelPermissions) || array_diff($modelPermissions, $dbRolePermissions)) {
                $issues[] = "權限關聯表與模型關聯不一致";
            }

            if (!empty($issues)) {
                $consistencyIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'issues' => $issues
                ];
            }
        }

        if (!empty($consistencyIssues)) {
            $this->validationErrors[] = [
                'type' => 'permission_consistency_issues',
                'message' => '權限一致性存在問題',
                'details' => $consistencyIssues
            ];
        }

        $this->validationResults['permission_consistency'] = [
            'total_roles_checked' => $roles->count(),
            'roles_with_consistency_issues' => count($consistencyIssues),
            'consistent_roles' => $roles->count() - count($consistencyIssues)
        ];
    }

    /**
     * 驗證使用者權限計算
     * 
     * @return void
     */
    private function validateUserPermissionCalculation(): void
    {
        $users = User::with('roles.permissions')->limit(10)->get();
        $userPermissionIssues = [];

        foreach ($users as $user) {
            $issues = [];

            try {
                // 方法 1: 透過角色關聯計算權限
                $permissionsViaRoles = $user->roles->flatMap(function ($role) {
                    return $role->getAllPermissions();
                })->unique('id');

                // 方法 2: 透過快取服務計算權限
                $permissionsViaCache = $this->cacheService->getUserAllPermissions($user->id);

                // 比較兩種方法的結果
                if ($permissionsViaRoles->count() !== $permissionsViaCache->count()) {
                    $issues[] = "權限計算結果不一致: 角色方法 {$permissionsViaRoles->count()}, 快取方法 {$permissionsViaCache->count()}";
                }

                // 檢查權限內容是否一致
                $rolePermissionIds = $permissionsViaRoles->pluck('id')->sort()->values();
                $cachePermissionIds = $permissionsViaCache->pluck('id')->sort()->values();

                if (!$rolePermissionIds->equals($cachePermissionIds)) {
                    $issues[] = "權限內容不一致";
                }

                // 測試特定權限檢查
                $testPermissions = ['users.view', 'roles.view', 'dashboard.view'];
                foreach ($testPermissions as $testPermission) {
                    $hasViaRoles = $permissionsViaRoles->contains('name', $testPermission);
                    $hasViaCache = $this->cacheService->userHasPermission($user->id, $testPermission);

                    if ($hasViaRoles !== $hasViaCache) {
                        $issues[] = "權限 '{$testPermission}' 檢查結果不一致";
                    }
                }

            } catch (\Exception $e) {
                $issues[] = "使用者權限計算異常: " . $e->getMessage();
            }

            if (!empty($issues)) {
                $userPermissionIssues[] = [
                    'user_id' => $user->id,
                    'username' => $user->username ?? $user->name,
                    'roles_count' => $user->roles->count(),
                    'issues' => $issues
                ];
            }
        }

        if (!empty($userPermissionIssues)) {
            $this->validationErrors[] = [
                'type' => 'user_permission_calculation_issues',
                'message' => '使用者權限計算存在問題',
                'details' => $userPermissionIssues
            ];
        }

        $this->validationResults['user_permission_calculation'] = [
            'users_tested' => $users->count(),
            'users_with_issues' => count($userPermissionIssues),
            'successful_calculations' => $users->count() - count($userPermissionIssues)
        ];
    }

    /**
     * 驗證快取一致性
     * 
     * @return void
     */
    private function validateCacheConsistency(): void
    {
        $roles = Role::with('permissions')->limit(5)->get();
        $cacheIssues = [];

        foreach ($roles as $role) {
            $issues = [];

            try {
                // 清除快取
                $this->cacheService->clearRoleCache($role->id);

                // 第一次取得（從資料庫）
                $permissionsFromDb = $role->getAllPermissions(false);

                // 第二次取得（從快取）
                $permissionsFromCache = $role->getAllPermissions(true);

                // 比較結果
                if ($permissionsFromDb->count() !== $permissionsFromCache->count()) {
                    $issues[] = "快取權限數量不一致: 資料庫 {$permissionsFromDb->count()}, 快取 {$permissionsFromCache->count()}";
                }

                $dbPermissionIds = $permissionsFromDb->pluck('id')->sort()->values();
                $cachePermissionIds = $permissionsFromCache->pluck('id')->sort()->values();

                if (!$dbPermissionIds->equals($cachePermissionIds)) {
                    $issues[] = "快取權限內容不一致";
                }

                // 測試快取服務方法
                $cacheServicePermissions = $this->cacheService->getRoleAllPermissions($role);
                if ($cacheServicePermissions->count() !== $permissionsFromDb->count()) {
                    $issues[] = "快取服務權限數量不一致";
                }

            } catch (\Exception $e) {
                $issues[] = "快取一致性檢查異常: " . $e->getMessage();
            }

            if (!empty($issues)) {
                $cacheIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'issues' => $issues
                ];
            }
        }

        if (!empty($cacheIssues)) {
            $this->validationWarnings[] = [
                'type' => 'cache_consistency_issues',
                'message' => '快取一致性存在問題',
                'details' => $cacheIssues
            ];
        }

        $this->validationResults['cache_consistency'] = [
            'roles_tested' => $roles->count(),
            'roles_with_cache_issues' => count($cacheIssues),
            'consistent_cache_roles' => $roles->count() - count($cacheIssues)
        ];
    }

    /**
     * 驗證效能影響
     * 
     * @return void
     */
    private function validatePerformanceImpact(): void
    {
        $performanceIssues = [];

        // 測試深層次角色的權限計算效能
        $deepRoles = Role::whereNotNull('parent_id')->get()->filter(function ($role) {
            return $this->calculateRoleDepth($role) >= 3;
        });

        foreach ($deepRoles->take(3) as $role) {
            $startTime = microtime(true);
            $permissions = $role->getAllPermissions();
            $endTime = microtime(true);
            
            $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

            if ($executionTime > 100) { // 超過 100ms
                $performanceIssues[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'depth' => $this->calculateRoleDepth($role),
                    'execution_time_ms' => round($executionTime, 2),
                    'permissions_count' => $permissions->count()
                ];
            }
        }

        if (!empty($performanceIssues)) {
            $this->validationWarnings[] = [
                'type' => 'performance_impact_issues',
                'message' => '權限繼承計算效能較慢',
                'details' => $performanceIssues
            ];
        }

        $this->validationResults['performance_impact'] = [
            'deep_roles_tested' => min($deepRoles->count(), 3),
            'slow_roles' => count($performanceIssues),
            'average_execution_time_ms' => $performanceIssues ? 
                collect($performanceIssues)->avg('execution_time_ms') : 0
        ];
    }

    /**
     * 檢查角色是否有循環依賴
     * 
     * @param Role $role
     * @param array $visited
     * @return bool
     */
    private function hasCircularDependency(Role $role, array $visited = []): bool
    {
        if (in_array($role->id, $visited)) {
            return true;
        }

        if (!$role->parent_id) {
            return false;
        }

        $visited[] = $role->id;
        $parent = Role::find($role->parent_id);

        if (!$parent) {
            return false;
        }

        return $this->hasCircularDependency($parent, $visited);
    }

    /**
     * 取得循環依賴鏈
     * 
     * @param Role $role
     * @return array
     */
    private function getCircularDependencyChain(Role $role): array
    {
        $chain = [];
        $current = $role;
        $visited = [];

        while ($current && !in_array($current->id, $visited)) {
            $visited[] = $current->id;
            $chain[] = [
                'id' => $current->id,
                'name' => $current->name
            ];

            if ($current->parent_id) {
                $current = Role::find($current->parent_id);
            } else {
                break;
            }
        }

        if ($current && in_array($current->id, $visited)) {
            $chain[] = [
                'id' => $current->id,
                'name' => $current->name,
                'note' => '循環點'
            ];
        }

        return $chain;
    }

    /**
     * 計算角色深度
     * 
     * @param Role $role
     * @return int
     */
    private function calculateRoleDepth(Role $role): int
    {
        $depth = 0;
        $current = $role;
        $visited = [];

        while ($current && $current->parent_id && !in_array($current->id, $visited)) {
            $visited[] = $current->id;
            $depth++;
            $current = Role::find($current->parent_id);
        }

        return $depth;
    }

    /**
     * 生成驗證報告
     * 
     * @return array
     */
    private function generateValidationReport(): array
    {
        $totalErrors = count($this->validationErrors);
        $totalWarnings = count($this->validationWarnings);

        // 計算整體狀態
        $overallStatus = 'excellent';
        if ($totalErrors > 0) {
            $overallStatus = 'critical';
        } elseif ($totalWarnings > 5) {
            $overallStatus = 'needs_improvement';
        } elseif ($totalWarnings > 0) {
            $overallStatus = 'good';
        }

        return [
            'overall_status' => $overallStatus,
            'validation_score' => $this->calculateValidationScore(),
            'summary' => [
                'total_errors' => $totalErrors,
                'total_warnings' => $totalWarnings,
                'validation_categories' => count($this->validationResults)
            ],
            'recommendations' => $this->generateRecommendations(),
            'validation_timestamp' => now()->toISOString()
        ];
    }

    /**
     * 計算驗證評分
     * 
     * @return int
     */
    private function calculateValidationScore(): int
    {
        $baseScore = 100;
        
        foreach ($this->validationErrors as $error) {
            $baseScore -= 15; // 每個錯誤扣 15 分
        }

        foreach ($this->validationWarnings as $warning) {
            $baseScore -= 5; // 每個警告扣 5 分
        }

        return max(0, $baseScore);
    }

    /**
     * 生成建議
     * 
     * @return array
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];

        if (!empty($this->validationErrors)) {
            $recommendations[] = '立即修復發現的錯誤，這些問題可能導致系統功能異常';
        }

        $circularDependencyErrors = collect($this->validationErrors)->where('type', 'circular_dependencies');
        if ($circularDependencyErrors->isNotEmpty()) {
            $recommendations[] = '修復角色層級循環依賴，這會導致權限繼承計算錯誤';
        }

        $depthWarnings = collect($this->validationWarnings)->where('type', 'deep_hierarchy_warning');
        if ($depthWarnings->isNotEmpty()) {
            $recommendations[] = '考慮簡化角色層級結構，過深的層級會影響效能';
        }

        $performanceWarnings = collect($this->validationWarnings)->where('type', 'performance_impact_issues');
        if ($performanceWarnings->isNotEmpty()) {
            $recommendations[] = '優化權限繼承計算效能，考慮增加快取或簡化層級結構';
        }

        if (empty($recommendations)) {
            $recommendations[] = '角色層級和權限繼承系統運作正常，建議定期執行驗證';
        }

        return $recommendations;
    }
}