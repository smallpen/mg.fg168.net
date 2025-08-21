<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 權限延遲載入服務
 * 
 * 提供權限資料的延遲載入和按需載入功能
 */
class PermissionLazyLoadingService
{
    /**
     * 權限快取服務
     */
    private PermissionCacheService $cacheService;

    /**
     * 預設頁面大小
     */
    const DEFAULT_PAGE_SIZE = 25;
    const LARGE_PAGE_SIZE = 100;
    const SMALL_PAGE_SIZE = 10;

    public function __construct(PermissionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 延遲載入權限列表
     * 
     * @param array $filters 篩選條件
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function lazyLoadPermissions(array $filters = [], int $page = 1, int $perPage = self::DEFAULT_PAGE_SIZE): LengthAwarePaginator
    {
        $cacheKey = 'lazy_permissions_' . md5(serialize($filters) . "_{$page}_{$perPage}");
        
        return Cache::remember($cacheKey, 900, function () use ($filters, $page, $perPage) {
            $query = Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description']);

            // 基本篩選條件
            $this->applyFilters($query, $filters);

            // 只在需要時載入關聯資料
            if (!empty($filters['include_relations'])) {
                $relations = $filters['include_relations'];
                
                if (in_array('roles', $relations)) {
                    $query->with('roles:id,name,display_name');
                }
                
                if (in_array('dependencies', $relations)) {
                    $query->with('dependencies:id,name,display_name,module');
                }
                
                if (in_array('dependents', $relations)) {
                    $query->with('dependents:id,name,display_name,module');
                }
            }

            // 只在需要時載入計數
            if (!empty($filters['include_counts'])) {
                $counts = $filters['include_counts'];
                
                if (in_array('roles', $counts)) {
                    $query->withCount('roles');
                }
                
                if (in_array('dependencies', $counts)) {
                    $query->withCount('dependencies');
                }
                
                if (in_array('dependents', $counts)) {
                    $query->withCount('dependents');
                }
            }

            // 排序
            $sortField = $filters['sort_field'] ?? 'module';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($sortField, $sortDirection);

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * 按需載入權限詳細資訊
     * 
     * @param int $permissionId 權限 ID
     * @param array $includes 需要載入的關聯資料
     * @return Permission|null
     */
    public function loadPermissionDetails(int $permissionId, array $includes = []): ?Permission
    {
        $cacheKey = "permission_details_{$permissionId}_" . md5(serialize($includes));
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionId, $includes) {
            $query = Permission::where('id', $permissionId);

            // 根據需求載入不同的關聯資料
            $relations = [];
            
            if (in_array('roles', $includes)) {
                $relations[] = 'roles:id,name,display_name';
            }
            
            if (in_array('dependencies', $includes)) {
                $relations[] = 'dependencies:id,name,display_name,module,type';
            }
            
            if (in_array('dependents', $includes)) {
                $relations[] = 'dependents:id,name,display_name,module,type';
            }
            
            if (in_array('users', $includes)) {
                $relations[] = 'roles.users:id,username,name,email';
            }

            if (!empty($relations)) {
                $query->with($relations);
            }

            // 根據需求載入計數
            $counts = [];
            
            if (in_array('role_count', $includes)) {
                $counts[] = 'roles';
            }
            
            if (in_array('dependency_count', $includes)) {
                $counts[] = 'dependencies';
            }
            
            if (in_array('dependent_count', $includes)) {
                $counts[] = 'dependents';
            }

            if (!empty($counts)) {
                $query->withCount($counts);
            }

            return $query->first();
        });
    }

    /**
     * 延遲載入權限樹狀結構
     * 
     * @param string|null $module 特定模組
     * @param int $maxDepth 最大深度
     * @return array
     */
    public function lazyLoadPermissionTree(?string $module = null, int $maxDepth = 3): array
    {
        $cacheKey = "permission_tree_lazy_{$module}_{$maxDepth}";
        
        return Cache::remember($cacheKey, 3600, function () use ($module, $maxDepth) {
            $query = Permission::select(['id', 'name', 'display_name', 'module', 'type']);
            
            if ($module) {
                $query->where('module', $module);
            }
            
            $permissions = $query->orderBy('module')
                                ->orderBy('type')
                                ->orderBy('name')
                                ->get();

            return $this->buildLazyTree($permissions, $maxDepth);
        });
    }

    /**
     * 分頁載入權限依賴關係
     * 
     * @param int $permissionId 權限 ID
     * @param string $type 類型：dependencies 或 dependents
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function lazyLoadPermissionRelations(int $permissionId, string $type = 'dependencies', int $page = 1, int $perPage = self::SMALL_PAGE_SIZE): LengthAwarePaginator
    {
        $cacheKey = "permission_relations_{$permissionId}_{$type}_{$page}_{$perPage}";
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionId, $type, $page, $perPage) {
            $permission = Permission::findOrFail($permissionId);
            
            if ($type === 'dependencies') {
                return $permission->dependencies()
                                 ->select(['permissions.id', 'permissions.name', 'permissions.display_name', 'permissions.module', 'permissions.type'])
                                 ->orderBy('permissions.module')
                                 ->orderBy('permissions.name')
                                 ->paginate($perPage, ['*'], 'page', $page);
            } else {
                return $permission->dependents()
                                 ->select(['permissions.id', 'permissions.name', 'permissions.display_name', 'permissions.module', 'permissions.type'])
                                 ->orderBy('permissions.module')
                                 ->orderBy('permissions.name')
                                 ->paginate($perPage, ['*'], 'page', $page);
            }
        });
    }

    /**
     * 延遲載入權限使用者列表
     * 
     * @param int $permissionId 權限 ID
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function lazyLoadPermissionUsers(int $permissionId, int $page = 1, int $perPage = self::DEFAULT_PAGE_SIZE): LengthAwarePaginator
    {
        $cacheKey = "permission_users_{$permissionId}_{$page}_{$perPage}";
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionId, $page, $perPage) {
            return User::select(['users.id', 'users.username', 'users.name', 'users.email', 'users.is_active'])
                      ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                      ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
                      ->where('role_permissions.permission_id', $permissionId)
                      ->with('roles:id,name,display_name')
                      ->distinct()
                      ->orderBy('users.username')
                      ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * 延遲載入權限角色列表
     * 
     * @param int $permissionId 權限 ID
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function lazyLoadPermissionRoles(int $permissionId, int $page = 1, int $perPage = self::SMALL_PAGE_SIZE): LengthAwarePaginator
    {
        $cacheKey = "permission_roles_{$permissionId}_{$page}_{$perPage}";
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionId, $page, $perPage) {
            $permission = Permission::findOrFail($permissionId);
            
            return $permission->roles()
                             ->select(['roles.id', 'roles.name', 'roles.display_name', 'roles.description'])
                             ->withCount('users')
                             ->orderBy('roles.name')
                             ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * 搜尋權限（延遲載入）
     * 
     * @param string $search 搜尋關鍵字
     * @param array $filters 篩選條件
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function lazySearchPermissions(string $search, array $filters = [], int $page = 1, int $perPage = self::DEFAULT_PAGE_SIZE): LengthAwarePaginator
    {
        $cacheKey = "permission_search_lazy_" . md5($search . serialize($filters) . "_{$page}_{$perPage}");
        
        return Cache::remember($cacheKey, 600, function () use ($search, $filters, $page, $perPage) {
            $query = Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description']);

            // 搜尋條件
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 應用篩選條件
            $this->applyFilters($query, $filters);

            // 只在需要時載入關聯資料
            if (!empty($filters['load_usage'])) {
                $query->withCount('roles');
            }

            return $query->orderBy('module')
                        ->orderBy('name')
                        ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    /**
     * 延遲載入模組權限統計
     * 
     * @param string $module 模組名稱
     * @return array
     */
    public function lazyLoadModuleStats(string $module): array
    {
        $cacheKey = "module_stats_lazy_{$module}";
        
        return Cache::remember($cacheKey, 3600, function () use ($module) {
            $permissions = Permission::where('module', $module)
                                   ->withCount('roles')
                                   ->get();

            $totalPermissions = $permissions->count();
            $usedPermissions = $permissions->where('roles_count', '>', 0)->count();
            $totalUsers = $this->getUserCountForModule($module);

            return [
                'module' => $module,
                'total_permissions' => $totalPermissions,
                'used_permissions' => $usedPermissions,
                'unused_permissions' => $totalPermissions - $usedPermissions,
                'usage_percentage' => $totalPermissions > 0 ? 
                                    round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
                'total_users' => $totalUsers,
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'type' => $permission->type,
                        'is_used' => $permission->roles_count > 0,
                        'role_count' => $permission->roles_count,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * 延遲載入權限類型統計
     * 
     * @param string $type 權限類型
     * @return array
     */
    public function lazyLoadTypeStats(string $type): array
    {
        $cacheKey = "type_stats_lazy_{$type}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type) {
            $permissions = Permission::where('type', $type)
                                   ->withCount('roles')
                                   ->get();

            $totalPermissions = $permissions->count();
            $usedPermissions = $permissions->where('roles_count', '>', 0)->count();

            return [
                'type' => $type,
                'total_permissions' => $totalPermissions,
                'used_permissions' => $usedPermissions,
                'unused_permissions' => $totalPermissions - $usedPermissions,
                'usage_percentage' => $totalPermissions > 0 ? 
                                    round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
                'modules' => $permissions->groupBy('module')->map(function ($modulePermissions, $module) {
                    $moduleTotal = $modulePermissions->count();
                    $moduleUsed = $modulePermissions->where('roles_count', '>', 0)->count();
                    
                    return [
                        'module' => $module,
                        'total' => $moduleTotal,
                        'used' => $moduleUsed,
                        'unused' => $moduleTotal - $moduleUsed,
                    ];
                })->values()->toArray(),
            ];
        });
    }

    /**
     * 預載入權限基本資訊
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return Collection
     */
    public function preloadBasicInfo(array $permissionIds): Collection
    {
        $cacheKey = 'preload_basic_' . md5(implode(',', sort($permissionIds)));
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionIds) {
            return Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description'])
                            ->whereIn('id', $permissionIds)
                            ->orderBy('module')
                            ->orderBy('name')
                            ->get()
                            ->keyBy('id');
        });
    }

    /**
     * 應用篩選條件
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['module']) && $filters['module'] !== 'all') {
            $query->where('module', $filters['module']);
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['usage'])) {
            switch ($filters['usage']) {
                case 'used':
                    $query->has('roles');
                    break;
                case 'unused':
                    $query->doesntHave('roles');
                    break;
                case 'system':
                    $query->where(function ($q) {
                        $q->where('module', 'system')
                          ->orWhere('module', 'auth')
                          ->orWhere('module', 'admin')
                          ->orWhere('name', 'like', 'system.%')
                          ->orWhere('name', 'like', 'auth.%')
                          ->orWhere('name', 'like', 'admin.core%');
                    });
                    break;
            }
        }

        if (!empty($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (!empty($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }
    }

    /**
     * 建立延遲載入樹狀結構
     * 
     * @param Collection $permissions
     * @param int $maxDepth
     * @return array
     */
    private function buildLazyTree(Collection $permissions, int $maxDepth): array
    {
        $tree = [];
        
        foreach ($permissions->groupBy('module') as $module => $modulePermissions) {
            $tree[$module] = [
                'name' => $module,
                'total_permissions' => $modulePermissions->count(),
                'types' => [],
            ];
            
            foreach ($modulePermissions->groupBy('type') as $type => $typePermissions) {
                $tree[$module]['types'][$type] = [
                    'name' => $type,
                    'count' => $typePermissions->count(),
                    'permissions' => $typePermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $permission->display_name,
                        ];
                    })->toArray(),
                ];
            }
        }
        
        return $tree;
    }

    /**
     * 取得模組的使用者數量
     * 
     * @param string $module
     * @return int
     */
    private function getUserCountForModule(string $module): int
    {
        return Cache::remember("module_user_count_{$module}", 1800, function () use ($module) {
            return DB::table('permissions')
                     ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                     ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                     ->where('permissions.module', $module)
                     ->distinct('user_roles.user_id')
                     ->count('user_roles.user_id');
        });
    }

    /**
     * 清除延遲載入快取
     * 
     * @param string|null $pattern 快取鍵模式
     * @return void
     */
    public function clearLazyCache(?string $pattern = null): void
    {
        if ($pattern) {
            // 這裡可以根據快取驅動實作模式匹配清除
            // 目前簡化處理
            Cache::flush();
        } else {
            Cache::flush();
        }
    }
}