<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * 角色優化服務
 * 
 * 提供延遲載入、分批處理等效能優化功能
 */
class RoleOptimizationService
{
    /**
     * 分批處理大小
     */
    private const BATCH_SIZE = 100;
    private const LARGE_BATCH_SIZE = 500;

    /**
     * 延遲載入角色列表
     * 
     * @param array $filters 篩選條件
     * @return LazyCollection
     */
    public function lazyLoadRoles(array $filters = []): LazyCollection
    {
        $query = Role::query();

        // 應用篩選條件
        $this->applyRoleFilters($query, $filters);

        // 使用 lazy() 方法進行延遲載入
        return $query->lazy(self::BATCH_SIZE);
    }

    /**
     * 分批處理角色權限同步
     * 
     * @param array $roleIds 角色 ID 陣列
     * @param array $permissionIds 權限 ID 陣列
     * @param string $operation 操作類型：sync, attach, detach
     * @return array 處理結果
     */
    public function batchProcessRolePermissions(array $roleIds, array $permissionIds, string $operation = 'sync'): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        // 分批處理角色
        $roleBatches = array_chunk($roleIds, self::BATCH_SIZE);

        foreach ($roleBatches as $batch) {
            try {
                DB::transaction(function () use ($batch, $permissionIds, $operation, &$results) {
                    $roles = Role::whereIn('id', $batch)->get();

                    foreach ($roles as $role) {
                        try {
                            switch ($operation) {
                                case 'sync':
                                    $role->permissions()->sync($permissionIds);
                                    break;
                                case 'attach':
                                    $role->permissions()->syncWithoutDetaching($permissionIds);
                                    break;
                                case 'detach':
                                    $role->permissions()->detach($permissionIds);
                                    break;
                            }
                            $results['success_count']++;
                        } catch (\Exception $e) {
                            $results['error_count']++;
                            $results['errors'][] = "角色 {$role->display_name}: " . $e->getMessage();
                        }
                    }
                });
            } catch (\Exception $e) {
                $results['error_count'] += count($batch);
                $results['errors'][] = "批次處理失敗: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 分批處理使用者角色指派
     * 
     * @param array $userIds 使用者 ID 陣列
     * @param array $roleIds 角色 ID 陣列
     * @param string $operation 操作類型：sync, attach, detach
     * @return array 處理結果
     */
    public function batchProcessUserRoles(array $userIds, array $roleIds, string $operation = 'sync'): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];

        // 分批處理使用者
        $userBatches = array_chunk($userIds, self::BATCH_SIZE);

        foreach ($userBatches as $batch) {
            try {
                DB::transaction(function () use ($batch, $roleIds, $operation, &$results) {
                    $users = \App\Models\User::whereIn('id', $batch)->get();

                    foreach ($users as $user) {
                        try {
                            switch ($operation) {
                                case 'sync':
                                    $user->roles()->sync($roleIds);
                                    break;
                                case 'attach':
                                    $user->roles()->syncWithoutDetaching($roleIds);
                                    break;
                                case 'detach':
                                    $user->roles()->detach($roleIds);
                                    break;
                            }
                            $results['success_count']++;
                        } catch (\Exception $e) {
                            $results['error_count']++;
                            $results['errors'][] = "使用者 {$user->name}: " . $e->getMessage();
                        }
                    }
                });
            } catch (\Exception $e) {
                $results['error_count'] += count($batch);
                $results['errors'][] = "批次處理失敗: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 優化的角色權限查詢
     * 
     * @param int $roleId 角色 ID
     * @param bool $includeInherited 是否包含繼承的權限
     * @return Collection
     */
    public function getOptimizedRolePermissions(int $roleId, bool $includeInherited = true): Collection
    {
        if (!$includeInherited) {
            // 只查詢直接權限，使用索引優化
            return Permission::select('permissions.*')
                            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                            ->where('role_permissions.role_id', $roleId)
                            ->orderBy('permissions.module')
                            ->orderBy('permissions.name')
                            ->get();
        }

        // 包含繼承權限的優化查詢
        return $this->getInheritedPermissionsOptimized($roleId);
    }

    /**
     * 優化的權限繼承查詢
     * 
     * @param int $roleId 角色 ID
     * @return Collection
     */
    private function getInheritedPermissionsOptimized(int $roleId): Collection
    {
        // 使用 CTE (Common Table Expression) 進行遞迴查詢
        $sql = "
            WITH RECURSIVE role_hierarchy AS (
                -- 基礎查詢：當前角色
                SELECT id, parent_id, name, display_name, 0 as level
                FROM roles 
                WHERE id = ?
                
                UNION ALL
                
                -- 遞迴查詢：父角色
                SELECT r.id, r.parent_id, r.name, r.display_name, rh.level + 1
                FROM roles r
                INNER JOIN role_hierarchy rh ON r.id = rh.parent_id
                WHERE rh.level < 10  -- 防止無限遞迴
            )
            SELECT DISTINCT p.*
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN role_hierarchy rh ON rp.role_id = rh.id
            ORDER BY p.module, p.name
        ";

        $permissions = DB::select($sql, [$roleId]);
        
        // 轉換為 Collection
        return collect($permissions)->map(function ($permission) {
            return Permission::newFromBuilder($permission);
        });
    }

    /**
     * 分批載入角色統計資訊
     * 
     * @param array $roleIds 角色 ID 陣列
     * @return array 統計資訊
     */
    public function batchLoadRoleStats(array $roleIds): array
    {
        $stats = [];
        $batches = array_chunk($roleIds, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            // 批次查詢使用者數量
            $userCounts = DB::table('user_roles')
                           ->select('role_id', DB::raw('COUNT(*) as user_count'))
                           ->whereIn('role_id', $batch)
                           ->groupBy('role_id')
                           ->pluck('user_count', 'role_id')
                           ->toArray();

            // 批次查詢權限數量
            $permissionCounts = DB::table('role_permissions')
                               ->select('role_id', DB::raw('COUNT(*) as permission_count'))
                               ->whereIn('role_id', $batch)
                               ->groupBy('role_id')
                               ->pluck('permission_count', 'role_id')
                               ->toArray();

            // 合併統計資訊
            foreach ($batch as $roleId) {
                $stats[$roleId] = [
                    'user_count' => $userCounts[$roleId] ?? 0,
                    'permission_count' => $permissionCounts[$roleId] ?? 0,
                ];
            }
        }

        return $stats;
    }

    /**
     * 優化的權限依賴解析
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return array 解析後的權限 ID 陣列
     */
    public function resolvePermissionDependenciesOptimized(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        // 使用 CTE 進行遞迴依賴解析
        $sql = "
            WITH RECURSIVE permission_deps AS (
                -- 基礎查詢：原始權限
                SELECT permission_id as id, 0 as level
                FROM (SELECT UNNEST(?) as permission_id) as base
                
                UNION ALL
                
                -- 遞迴查詢：依賴的權限
                SELECT pd.depends_on_permission_id as id, deps.level + 1
                FROM permission_dependencies pd
                INNER JOIN permission_deps deps ON pd.permission_id = deps.id
                WHERE deps.level < 5  -- 防止無限遞迴
            )
            SELECT DISTINCT id FROM permission_deps
        ";

        // 對於不支援 UNNEST 的資料庫，使用替代方案
        if (DB::getDriverName() === 'mysql') {
            return $this->resolvePermissionDependenciesMySQL($permissionIds);
        }

        $result = DB::select($sql, ['{' . implode(',', $permissionIds) . '}']);
        
        return collect($result)->pluck('id')->toArray();
    }

    /**
     * MySQL 版本的權限依賴解析
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return array 解析後的權限 ID 陣列
     */
    private function resolvePermissionDependenciesMySQL(array $permissionIds): array
    {
        $resolvedIds = collect($permissionIds);
        $processedIds = collect();
        $maxIterations = 5; // 防止無限循環
        $iteration = 0;

        while ($resolvedIds->diff($processedIds)->isNotEmpty() && $iteration < $maxIterations) {
            $newIds = $resolvedIds->diff($processedIds);
            $processedIds = $processedIds->merge($newIds);

            // 批次查詢依賴關係
            $dependencies = DB::table('permission_dependencies')
                             ->whereIn('permission_id', $newIds->toArray())
                             ->pluck('depends_on_permission_id')
                             ->unique();

            $resolvedIds = $resolvedIds->merge($dependencies)->unique();
            $iteration++;
        }

        return $resolvedIds->values()->toArray();
    }

    /**
     * 預載入角色關聯資料
     * 
     * @param Collection $roles 角色集合
     * @param array $relations 要預載入的關聯
     * @return Collection
     */
    public function eagerLoadRoleRelations(Collection $roles, array $relations = []): Collection
    {
        $defaultRelations = ['permissions', 'users', 'parent', 'children'];
        $loadRelations = empty($relations) ? $defaultRelations : $relations;

        // 分批預載入關聯，避免記憶體問題
        $roleIds = $roles->pluck('id')->toArray();
        $batches = array_chunk($roleIds, self::BATCH_SIZE);

        $allRoles = collect();

        foreach ($batches as $batch) {
            $batchRoles = Role::with($loadRelations)
                             ->whereIn('id', $batch)
                             ->get();
            $allRoles = $allRoles->merge($batchRoles);
        }

        return $allRoles;
    }

    /**
     * 優化的角色搜尋
     * 
     * @param string $term 搜尋關鍵字
     * @param array $options 搜尋選項
     * @return Collection
     */
    public function optimizedRoleSearch(string $term, array $options = []): Collection
    {
        $query = Role::query();

        // 使用全文搜尋索引（如果可用）
        if ($this->supportsFullTextSearch()) {
            $query->whereRaw("MATCH(name, display_name, description) AGAINST(? IN BOOLEAN MODE)", [$term . '*']);
        } else {
            // 使用 LIKE 查詢，但優化索引使用
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term . '%')  // 使用前綴匹配，可以利用索引
                  ->orWhere('display_name', 'like', $term . '%')
                  ->orWhere('description', 'like', '%' . $term . '%');
            });
        }

        // 應用其他篩選條件
        if (isset($options['is_active'])) {
            $query->where('is_active', $options['is_active']);
        }

        if (isset($options['is_system_role'])) {
            $query->where('is_system_role', $options['is_system_role']);
        }

        $limit = $options['limit'] ?? 50;

        return $query->limit($limit)->get();
    }

    /**
     * 應用角色篩選條件
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyRoleFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search . '%')
                  ->orWhere('display_name', 'like', $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_system_role'])) {
            $query->where('is_system_role', $filters['is_system_role']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }
    }

    /**
     * 檢查是否支援全文搜尋
     * 
     * @return bool
     */
    private function supportsFullTextSearch(): bool
    {
        try {
            // 檢查資料庫是否支援全文搜尋，並且有對應的索引
            $driver = DB::getDriverName();
            if (!in_array($driver, ['mysql', 'pgsql'])) {
                return false;
            }

            // 檢查是否有全文索引
            if ($driver === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM roles WHERE Index_type = 'FULLTEXT'");
                return !empty($indexes);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 取得記憶體使用統計
     * 
     * @return array
     */
    public function getMemoryStats(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * 清理未使用的關聯資料
     * 
     * @return array 清理結果
     */
    public function cleanupUnusedRelations(): array
    {
        $results = [
            'orphaned_role_permissions' => 0,
            'orphaned_user_roles' => 0,
            'orphaned_permission_dependencies' => 0,
        ];

        DB::transaction(function () use (&$results) {
            // 清理孤立的角色權限關聯
            $results['orphaned_role_permissions'] = DB::table('role_permissions')
                ->leftJoin('roles', 'role_permissions.role_id', '=', 'roles.id')
                ->leftJoin('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->whereNull('roles.id')
                ->orWhereNull('permissions.id')
                ->delete();

            // 清理孤立的使用者角色關聯
            $results['orphaned_user_roles'] = DB::table('user_roles')
                ->leftJoin('users', 'user_roles.user_id', '=', 'users.id')
                ->leftJoin('roles', 'user_roles.role_id', '=', 'roles.id')
                ->whereNull('users.id')
                ->orWhereNull('roles.id')
                ->delete();

            // 清理孤立的權限依賴關聯
            $results['orphaned_permission_dependencies'] = DB::table('permission_dependencies')
                ->leftJoin('permissions as p1', 'permission_dependencies.permission_id', '=', 'p1.id')
                ->leftJoin('permissions as p2', 'permission_dependencies.depends_on_permission_id', '=', 'p2.id')
                ->whereNull('p1.id')
                ->orWhereNull('p2.id')
                ->delete();
        });

        return $results;
    }
}