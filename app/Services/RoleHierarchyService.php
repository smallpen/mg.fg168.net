<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 角色層級管理服務
 * 
 * 處理角色層級關係和權限繼承邏輯
 */
class RoleHierarchyService
{
    /**
     * 更新角色的父角色並處理權限繼承
     * 
     * @param Role $role 要更新的角色
     * @param int|null $newParentId 新的父角色 ID
     * @return bool
     * @throws \Exception
     */
    public function updateRoleParent(Role $role, ?int $newParentId): bool
    {
        // 檢查循環依賴
        if ($newParentId && $role->hasCircularDependency($newParentId)) {
            throw new \Exception('設定父角色會造成循環依賴');
        }
        
        // 檢查系統角色保護
        if ($role->is_system_role) {
            throw new \Exception('系統角色不能修改層級關係');
        }
        
        $oldParentId = $role->parent_id;
        
        try {
            DB::beginTransaction();
            
            // 更新父角色
            $role->update(['parent_id' => $newParentId]);
            
            // 如果父角色有變更，更新權限繼承
            if ($oldParentId !== $newParentId) {
                $this->updatePermissionInheritance($role);
            }
            
            DB::commit();
            
            Log::info('角色層級更新成功', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'old_parent_id' => $oldParentId,
                'new_parent_id' => $newParentId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('角色層級更新失敗', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * 更新權限繼承
     * 
     * @param Role $role 角色實例
     * @return void
     */
    public function updatePermissionInheritance(Role $role): void
    {
        // 清除相關的權限快取
        $this->clearPermissionCache($role);
        
        // 遞迴更新所有子角色的權限快取
        $descendants = $role->getDescendants();
        foreach ($descendants as $descendant) {
            $this->clearPermissionCache($descendant);
        }
        
        Log::info('權限繼承更新完成', [
            'role_id' => $role->id,
            'affected_descendants' => $descendants->count()
        ]);
    }
    
    /**
     * 清除角色的權限快取
     * 
     * @param Role $role 角色實例
     * @return void
     */
    private function clearPermissionCache(Role $role): void
    {
        $cacheKeys = [
            "role_all_permissions_{$role->id}",
            "role_direct_permissions_{$role->id}",
            "role_inherited_permissions_{$role->id}",
            "role_permission_stats_{$role->id}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
    
    /**
     * 批量移動角色到新的父角色下
     * 
     * @param array $roleIds 角色 ID 陣列
     * @param int|null $newParentId 新的父角色 ID
     * @return array 操作結果
     */
    public function bulkMoveRoles(array $roleIds, ?int $newParentId): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => []
        ];
        
        foreach ($roleIds as $roleId) {
            try {
                $role = Role::findOrFail($roleId);
                $this->updateRoleParent($role, $newParentId);
                $results['success_count']++;
                
            } catch (\Exception $e) {
                $results['error_count']++;
                $results['errors'][] = [
                    'role_id' => $roleId,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * 取得角色層級樹狀結構
     * 
     * @param array $options 選項
     * @return Collection
     */
    public function getHierarchyTree(array $options = []): Collection
    {
        $includeInactive = $options['include_inactive'] ?? false;
        $includeSystemRoles = $options['include_system_roles'] ?? true;
        $search = $options['search'] ?? null;
        
        $query = Role::with(['children' => function ($query) use ($includeInactive, $includeSystemRoles) {
            $query->orderBy('display_name');
            if (!$includeInactive) {
                $query->where('is_active', true);
            }
            if (!$includeSystemRoles) {
                $query->where('is_system_role', false);
            }
        }])
        ->whereNull('parent_id')
        ->orderBy('display_name');
        
        // 套用篩選條件
        if (!$includeInactive) {
            $query->where('is_active', true);
        }
        
        if (!$includeSystemRoles) {
            $query->where('is_system_role', false);
        }
        
        // 搜尋功能
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        return $query->withCount(['users', 'permissions'])->get();
    }
    
    /**
     * 取得角色層級統計資訊
     * 
     * @return array
     */
    public function getHierarchyStats(): array
    {
        return Cache::remember('role_hierarchy_stats', 300, function () {
            return [
                'total_roles' => Role::count(),
                'root_roles' => Role::whereNull('parent_id')->count(),
                'child_roles' => Role::whereNotNull('parent_id')->count(),
                'max_depth' => $this->calculateMaxDepth(),
                'system_roles' => Role::where('is_system_role', true)->count(),
                'active_roles' => Role::where('is_active', true)->count(),
                'roles_with_children' => Role::whereHas('children')->count(),
                'orphaned_roles' => $this->getOrphanedRolesCount(),
            ];
        });
    }
    
    /**
     * 計算最大層級深度
     * 
     * @return int
     */
    private function calculateMaxDepth(): int
    {
        $maxDepth = 0;
        $rootRoles = Role::whereNull('parent_id')->get();
        
        foreach ($rootRoles as $role) {
            $depth = $this->calculateRoleDepth($role);
            $maxDepth = max($maxDepth, $depth);
        }
        
        return $maxDepth;
    }
    
    /**
     * 遞迴計算角色深度
     * 
     * @param Role $role 角色實例
     * @param int $currentDepth 當前深度
     * @return int
     */
    private function calculateRoleDepth(Role $role, int $currentDepth = 0): int
    {
        $maxChildDepth = $currentDepth;
        
        foreach ($role->children as $child) {
            $childDepth = $this->calculateRoleDepth($child, $currentDepth + 1);
            $maxChildDepth = max($maxChildDepth, $childDepth);
        }
        
        return $maxChildDepth;
    }
    
    /**
     * 取得孤立角色數量（父角色不存在的角色）
     * 
     * @return int
     */
    private function getOrphanedRolesCount(): int
    {
        return Role::whereNotNull('parent_id')
                  ->whereNotExists(function ($query) {
                      $query->select(DB::raw(1))
                            ->from('roles as parent_roles')
                            ->whereColumn('parent_roles.id', 'roles.parent_id');
                  })
                  ->count();
    }
    
    /**
     * 修復孤立角色（將父角色不存在的角色設為根角色）
     * 
     * @return int 修復的角色數量
     */
    public function fixOrphanedRoles(): int
    {
        $orphanedRoles = Role::whereNotNull('parent_id')
                            ->whereNotExists(function ($query) {
                                $query->select(DB::raw(1))
                                      ->from('roles as parent_roles')
                                      ->whereColumn('parent_roles.id', 'roles.parent_id');
                            })
                            ->get();
        
        $fixedCount = 0;
        
        foreach ($orphanedRoles as $role) {
            try {
                $role->update(['parent_id' => null]);
                $this->updatePermissionInheritance($role);
                $fixedCount++;
                
                Log::info('修復孤立角色', [
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]);
                
            } catch (\Exception $e) {
                Log::error('修復孤立角色失敗', [
                    'role_id' => $role->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $fixedCount;
    }
    
    /**
     * 驗證角色層級結構的完整性
     * 
     * @return array 驗證結果
     */
    public function validateHierarchyIntegrity(): array
    {
        $issues = [];
        
        // 檢查循環依賴
        $circularDependencies = $this->findCircularDependencies();
        if (!empty($circularDependencies)) {
            $issues['circular_dependencies'] = $circularDependencies;
        }
        
        // 檢查孤立角色
        $orphanedRoles = $this->getOrphanedRoles();
        if (!empty($orphanedRoles)) {
            $issues['orphaned_roles'] = $orphanedRoles;
        }
        
        // 檢查深度過深的角色
        $deepRoles = $this->findDeepRoles(5); // 超過 5 層的角色
        if (!empty($deepRoles)) {
            $issues['deep_roles'] = $deepRoles;
        }
        
        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
            'total_issues' => array_sum(array_map('count', $issues))
        ];
    }
    
    /**
     * 尋找循環依賴
     * 
     * @return array
     */
    private function findCircularDependencies(): array
    {
        $circularDependencies = [];
        $roles = Role::whereNotNull('parent_id')->get();
        
        foreach ($roles as $role) {
            if ($this->hasCircularDependencyInChain($role)) {
                $circularDependencies[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'chain' => $this->getParentChain($role)
                ];
            }
        }
        
        return $circularDependencies;
    }
    
    /**
     * 檢查角色鏈中是否有循環依賴
     * 
     * @param Role $role 角色實例
     * @param array $visited 已訪問的角色 ID
     * @return bool
     */
    private function hasCircularDependencyInChain(Role $role, array $visited = []): bool
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
        
        return $this->hasCircularDependencyInChain($parent, $visited);
    }
    
    /**
     * 取得角色的父角色鏈
     * 
     * @param Role $role 角色實例
     * @return array
     */
    private function getParentChain(Role $role): array
    {
        $chain = [];
        $current = $role;
        
        while ($current && !in_array($current->id, array_column($chain, 'id'))) {
            $chain[] = [
                'id' => $current->id,
                'name' => $current->name,
                'display_name' => $current->display_name
            ];
            
            $current = $current->parent;
        }
        
        return $chain;
    }
    
    /**
     * 取得孤立角色
     * 
     * @return array
     */
    private function getOrphanedRoles(): array
    {
        return Role::whereNotNull('parent_id')
                  ->whereNotExists(function ($query) {
                      $query->select(DB::raw(1))
                            ->from('roles as parent_roles')
                            ->whereColumn('parent_roles.id', 'roles.parent_id');
                  })
                  ->select('id', 'name', 'display_name', 'parent_id')
                  ->get()
                  ->toArray();
    }
    
    /**
     * 尋找深度過深的角色
     * 
     * @param int $maxDepth 最大允許深度
     * @return array
     */
    private function findDeepRoles(int $maxDepth): array
    {
        $deepRoles = [];
        $rootRoles = Role::whereNull('parent_id')->get();
        
        foreach ($rootRoles as $role) {
            $this->findDeepRolesRecursive($role, 0, $maxDepth, $deepRoles);
        }
        
        return $deepRoles;
    }
    
    /**
     * 遞迴尋找深度過深的角色
     * 
     * @param Role $role 角色實例
     * @param int $currentDepth 當前深度
     * @param int $maxDepth 最大允許深度
     * @param array &$deepRoles 深度過深的角色陣列
     * @return void
     */
    private function findDeepRolesRecursive(Role $role, int $currentDepth, int $maxDepth, array &$deepRoles): void
    {
        if ($currentDepth > $maxDepth) {
            $deepRoles[] = [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'depth' => $currentDepth,
                'path' => $this->getRolePath($role)
            ];
        }
        
        foreach ($role->children as $child) {
            $this->findDeepRolesRecursive($child, $currentDepth + 1, $maxDepth, $deepRoles);
        }
    }
    
    /**
     * 取得角色的完整路徑
     * 
     * @param Role $role 角色實例
     * @return string
     */
    private function getRolePath(Role $role): string
    {
        $path = [$role->display_name];
        $current = $role->parent;
        
        while ($current) {
            array_unshift($path, $current->display_name);
            $current = $current->parent;
        }
        
        return implode(' > ', $path);
    }
}