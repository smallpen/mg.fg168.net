<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 權限資料存取實作
 */
class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * 取得分頁權限列表（效能優化版本）
     * 
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function getPaginatedPermissions(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // 修正快取鍵，包含當前頁面資訊
        $currentPage = request()->get('page', 1);
        $cacheKey = 'paginated_permissions_' . md5(serialize($filters) . "_{$perPage}_page_{$currentPage}");
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            $query = Permission::select([
                'id', 'name', 'display_name', 'description', 'module', 'type', 
                'created_at', 'updated_at'
            ]);

            // 只在需要時載入關聯資料
            if (!empty($filters['include_relations'])) {
                if (in_array('roles', $filters['include_relations'])) {
                    $query->with('roles:id,name,display_name');
                }
                if (in_array('dependencies', $filters['include_relations'])) {
                    $query->with('dependencies:id,name,display_name,module');
                }
                if (in_array('dependents', $filters['include_relations'])) {
                    $query->with('dependents:id,name,display_name,module');
                }
            }

            // 只在需要時載入計數
            if (!empty($filters['include_counts'])) {
                $counts = [];
                if (in_array('roles', $filters['include_counts'])) {
                    $counts[] = 'roles';
                }
                if (in_array('dependencies', $filters['include_counts'])) {
                    $counts[] = 'dependencies';
                }
                if (in_array('dependents', $filters['include_counts'])) {
                    $counts[] = 'dependents';
                }
                if (!empty($counts)) {
                    $query->withCount($counts);
                }
            }

            // 搜尋篩選（使用索引優化）
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function (Builder $q) use ($search) {
                    // 優先使用有索引的欄位
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                    
                    // 描述搜尋放在最後（沒有索引）
                    if (strlen($search) >= 3) { // 只有搜尋詞較長時才搜尋描述
                        $q->orWhere('description', 'like', "%{$search}%");
                    }
                });
            }

            // 模組篩選（使用複合索引）
            if (!empty($filters['module']) && $filters['module'] !== 'all') {
                $query->where('module', $filters['module']);
            }

            // 類型篩選（使用複合索引）
            if (!empty($filters['type']) && $filters['type'] !== 'all') {
                $query->where('type', $filters['type']);
            }

            // 使用狀態篩選
            if (!empty($filters['usage'])) {
                switch ($filters['usage']) {
                    case 'used':
                        $query->has('roles');
                        break;
                    case 'unused':
                        $query->doesntHave('roles');
                        break;
                    case 'marked_unused':
                        $markedIds = \Illuminate\Support\Facades\Cache::get('marked_unused_permissions', []);
                        if (!empty($markedIds)) {
                            $query->whereIn('id', $markedIds);
                        } else {
                            $query->whereRaw('1 = 0'); // 沒有標記的權限時返回空結果
                        }
                        break;
                    case 'low_usage':
                        // 使用子查詢優化效能
                        $query->whereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                    ->from('role_permissions')
                                    ->whereColumn('role_permissions.permission_id', 'permissions.id')
                                    ->havingRaw('COUNT(*) <= 2');
                        });
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

            // 日期範圍篩選
            if (!empty($filters['created_after'])) {
                $query->where('created_at', '>=', $filters['created_after']);
            }
            if (!empty($filters['created_before'])) {
                $query->where('created_at', '<=', $filters['created_before']);
            }

            // 排序（使用複合索引優化）
            $sortField = $filters['sort_field'] ?? 'module';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            if ($sortField === 'role_count') {
                // 如果沒有載入計數，則載入
                if (empty($filters['include_counts']) || !in_array('roles', $filters['include_counts'])) {
                    $query->withCount('roles');
                }
                $query->orderBy('roles_count', $sortDirection);
            } else {
                // 使用複合索引進行排序
                if ($sortField === 'module') {
                    $query->orderBy('module', $sortDirection)
                          ->orderBy('type', 'asc')
                          ->orderBy('name', 'asc');
                } elseif ($sortField === 'type') {
                    $query->orderBy('type', $sortDirection)
                          ->orderBy('module', 'asc')
                          ->orderBy('name', 'asc');
                } else {
                    $query->orderBy($sortField, $sortDirection);
                }
            }

            return $query->paginate($perPage);
        });
    }

    /**
     * 取得按模組分組的權限（效能優化版本）
     * 
     * @return Collection
     */
    public function getPermissionsByModule(): Collection
    {
        return \Illuminate\Support\Facades\Cache::remember('permissions_by_module_optimized', 3600, function () {
            // 使用複合索引進行高效查詢
            return Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description'])
                            ->with([
                                'dependencies:id,name,display_name,module',
                                'dependents:id,name,display_name,module'
                            ])
                            ->withCount('roles')
                            ->orderBy('module')
                            ->orderBy('type')
                            ->orderBy('name')
                            ->get()
                            ->groupBy('module');
        });
    }

    /**
     * 批量取得權限基本資訊（效能優化）
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return Collection
     */
    public function getBatchPermissionsBasicInfo(array $permissionIds): Collection
    {
        if (empty($permissionIds)) {
            return collect();
        }

        $cacheKey = 'batch_permissions_basic_' . md5(implode(',', sort($permissionIds)));
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($permissionIds) {
            return Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description'])
                            ->whereIn('id', $permissionIds)
                            ->orderBy('module')
                            ->orderBy('type')
                            ->orderBy('name')
                            ->get()
                            ->keyBy('id');
        });
    }

    /**
     * 高效能權限搜尋
     * 
     * @param string $search 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @param int $limit 結果限制
     * @return Collection
     */
    public function searchPermissionsOptimized(string $search, array $filters = [], int $limit = 50): Collection
    {
        $cacheKey = 'search_permissions_' . md5($search . serialize($filters) . "_{$limit}");
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($search, $filters, $limit) {
            $query = Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description']);

            // 優化搜尋條件
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    // 精確匹配優先
                    $q->where('name', $search)
                      ->orWhere('display_name', $search)
                      // 然後是前綴匹配
                      ->orWhere('name', 'like', "{$search}%")
                      ->orWhere('display_name', 'like', "{$search}%")
                      // 最後是模糊匹配
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                    
                    // 只有搜尋詞較長時才搜尋描述
                    if (strlen($search) >= 3) {
                        $q->orWhere('description', 'like', "%{$search}%");
                    }
                });
            }

            // 應用篩選條件
            if (!empty($filters['module'])) {
                $query->where('module', $filters['module']);
            }
            
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            return $query->orderByRaw("
                CASE 
                    WHEN name = ? THEN 1
                    WHEN display_name = ? THEN 2
                    WHEN name LIKE ? THEN 3
                    WHEN display_name LIKE ? THEN 4
                    ELSE 5
                END
            ", [$search, $search, "{$search}%", "{$search}%"])
            ->orderBy('module')
            ->orderBy('name')
            ->limit($limit)
            ->get();
        });
    }

    /**
     * 取得權限依賴關係
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getPermissionDependencies(int $permissionId): Collection
    {
        $permission = Permission::findOrFail($permissionId);
        return $permission->dependencies()->orderBy('module')->orderBy('name')->get();
    }

    /**
     * 取得依賴此權限的其他權限
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getPermissionDependents(int $permissionId): Collection
    {
        $permission = Permission::findOrFail($permissionId);
        return $permission->dependents()->orderBy('module')->orderBy('name')->get();
    }

    /**
     * 建立權限
     * 
     * @param array $data 權限資料
     * @return Permission
     */
    public function createPermission(array $data): Permission
    {
        return Permission::create($data);
    }

    /**
     * 更新權限
     * 
     * @param Permission $permission 權限實例
     * @param array $data 更新資料
     * @return bool
     */
    public function updatePermission(Permission $permission, array $data): bool
    {
        return $permission->update($data);
    }

    /**
     * 刪除權限
     * 
     * @param Permission $permission 權限實例
     * @return bool
     */
    public function deletePermission(Permission $permission): bool
    {
        // 檢查是否可以刪除
        if (!$this->canDeletePermission($permission)) {
            return false;
        }

        // 移除所有依賴關係
        $permission->dependencies()->detach();
        $permission->dependents()->detach();

        // 移除角色關聯
        $permission->roles()->detach();

        return $permission->delete();
    }

    /**
     * 同步權限依賴關係
     * 
     * @param Permission $permission 權限實例
     * @param array $dependencyIds 依賴權限 ID 陣列
     * @return void
     */
    public function syncDependencies(Permission $permission, array $dependencyIds): void
    {
        // 檢查循環依賴
        foreach ($dependencyIds as $dependencyId) {
            if ($this->hasCircularDependency($permission->id, [$dependencyId])) {
                throw new \InvalidArgumentException("無法建立循環依賴關係");
            }
        }

        $permission->dependencies()->sync($dependencyIds);
    }

    /**
     * 取得未使用的權限
     * 
     * @return Collection
     */
    public function getUnusedPermissions(): Collection
    {
        return Permission::doesntHave('roles')
                        ->orderBy('module')
                        ->orderBy('name')
                        ->get();
    }

    /**
     * 取得權限使用統計
     * 
     * @return array
     */
    public function getPermissionUsageStats(): array
    {
        $totalPermissions = Permission::count();
        $usedPermissions = Permission::has('roles')->count();
        $unusedPermissions = $totalPermissions - $usedPermissions;

        $moduleStats = Permission::select('module')
                                ->withCount('roles')
                                ->get()
                                ->groupBy('module')
                                ->map(function ($permissions, $module) {
                                    $totalUsers = $this->getUserCountForModule($module);
                                    $totalPermissions = $permissions->count();
                                    $usedPermissions = $permissions->where('roles_count', '>', 0)->count();
                                    
                                    return [
                                        'module' => $module,
                                        'total_permissions' => $totalPermissions,
                                        'used_permissions' => $usedPermissions,
                                        'unused_permissions' => $totalPermissions - $usedPermissions,
                                        'usage_percentage' => $totalPermissions > 0 ? 
                                                            round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
                                        'total_users' => $totalUsers,
                                        'average_permissions_per_user' => $totalUsers > 0 ? 
                                                                        round($usedPermissions / $totalUsers, 2) : 0,
                                    ];
                                })
                                ->values()
                                ->toArray();

        // 系統權限統計
        $systemPermissions = Permission::where(function ($query) {
            $query->where('module', 'system')
                  ->orWhere('module', 'auth')
                  ->orWhere('module', 'admin')
                  ->orWhere('name', 'like', 'system.%')
                  ->orWhere('name', 'like', 'auth.%')
                  ->orWhere('name', 'like', 'admin.core%');
        })->count();

        return [
            'total_permissions' => $totalPermissions,
            'used_permissions' => $usedPermissions,
            'unused_permissions' => $unusedPermissions,
            'system_permissions' => $systemPermissions,
            'custom_permissions' => $totalPermissions - $systemPermissions,
            'usage_percentage' => $totalPermissions > 0 ? round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
            'unused_percentage' => $totalPermissions > 0 ? round(($unusedPermissions / $totalPermissions) * 100, 2) : 0,
            'total_roles' => \App\Models\Role::count(),
            'total_users' => \App\Models\User::count(),
            'average_permissions_per_role' => $this->getAveragePermissionsPerRole(),
            'average_permissions_per_user' => $this->getAveragePermissionsPerUser(),
            'modules' => $moduleStats,
        ];
    }

    /**
     * 取得模組的使用者數量
     * 
     * @param string $module
     * @return int
     */
    private function getUserCountForModule(string $module): int
    {
        return DB::table('permissions')
                 ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                 ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                 ->where('permissions.module', $module)
                 ->distinct('user_roles.user_id')
                 ->count('user_roles.user_id');
    }

    /**
     * 取得每個角色的平均權限數
     * 
     * @return float
     */
    private function getAveragePermissionsPerRole(): float
    {
        $totalRoles = \App\Models\Role::count();
        if ($totalRoles === 0) return 0;
        
        $totalPermissions = DB::table('role_permissions')->count();
        return round($totalPermissions / $totalRoles, 2);
    }

    /**
     * 取得每個使用者的平均權限數
     * 
     * @return float
     */
    private function getAveragePermissionsPerUser(): float
    {
        $totalUsers = \App\Models\User::count();
        if ($totalUsers === 0) return 0;
        
        $totalUserPermissions = DB::table('user_roles')
                                 ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
                                 ->count();
        
        return round($totalUserPermissions / $totalUsers, 2);
    }

    /**
     * 取得使用頻率統計
     * 
     * @return array
     */
    public function getUsageFrequencyStats(): array
    {
        $permissions = Permission::with('roles.users')->get();
        
        $frequencyLevels = [
            'very_high' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'very_low' => 0,
        ];
        
        foreach ($permissions as $permission) {
            $level = $permission->getFrequencyLevel();
            $frequencyLevels[$level]++;
        }
        
        return $frequencyLevels;
    }

    /**
     * 取得標記為未使用的權限
     * 
     * @return Collection
     */
    public function getMarkedUnusedPermissions(): Collection
    {
        $markedIds = \Illuminate\Support\Facades\Cache::get('marked_unused_permissions', []);
        
        if (empty($markedIds)) {
            return collect();
        }
        
        return Permission::whereIn('id', $markedIds)
                        ->with(['dependencies', 'dependents'])
                        ->orderBy('module')
                        ->orderBy('name')
                        ->get();
    }

    /**
     * 匯出權限資料
     * 
     * @param array $filters 篩選條件
     * @return array
     */
    public function exportPermissions(array $filters = []): array
    {
        $query = Permission::with(['dependencies', 'dependents']);

        // 應用篩選條件
        if (!empty($filters['modules'])) {
            $query->whereIn('module', $filters['modules']);
        }

        if (!empty($filters['types'])) {
            $query->whereIn('type', $filters['types']);
        }

        if (!empty($filters['usage_status'])) {
            switch ($filters['usage_status']) {
                case 'used':
                    $query->has('roles');
                    break;
                case 'unused':
                    $query->doesntHave('roles');
                    break;
            }
        }

        if (!empty($filters['permission_ids'])) {
            $query->whereIn('id', $filters['permission_ids']);
        }

        $permissions = $query->orderBy('module')->orderBy('name')->get();

        return $permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'module' => $permission->module,
                'type' => $permission->type,
                'dependencies' => $permission->dependencies->pluck('name')->toArray(),
                'created_at' => $permission->created_at?->toISOString(),
                'updated_at' => $permission->updated_at?->toISOString(),
            ];
        })->toArray();
    }

    /**
     * 匯入權限資料
     * 
     * @param array $data 權限資料
     * @param array $options 匯入選項
     * @return array 匯入結果
     */
    public function importPermissions(array $data, array $options = []): array
    {
        $results = [
            'success' => false,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'conflicts' => [],
            'warnings' => [],
        ];

        // 預設選項
        $defaultOptions = [
            'conflict_resolution' => 'skip',
            'validate_dependencies' => true,
            'dry_run' => false,
        ];
        $options = array_merge($defaultOptions, $options);

        DB::beginTransaction();

        try {
            foreach ($data as $permissionData) {
                $dependencies = $permissionData['dependencies'] ?? [];
                unset($permissionData['dependencies'], $permissionData['created_at'], $permissionData['updated_at']);

                $existingPermission = Permission::where('name', $permissionData['name'])->first();

                if ($existingPermission) {
                    // 處理衝突
                    switch ($options['conflict_resolution']) {
                        case 'skip':
                            $results['skipped']++;
                            break;
                        case 'update':
                            if (!$options['dry_run']) {
                                $existingPermission->update($permissionData);
                            }
                            $results['updated']++;
                            break;
                        case 'merge':
                            // 簡單的合併邏輯
                            $mergedData = array_merge($existingPermission->toArray(), $permissionData);
                            if (!$options['dry_run']) {
                                $existingPermission->update($mergedData);
                            }
                            $results['updated']++;
                            break;
                    }
                    $permission = $existingPermission;
                } else {
                    // 建立新權限
                    if (!$options['dry_run']) {
                        $permission = Permission::create($permissionData);
                    } else {
                        $permission = new Permission($permissionData);
                        $permission->id = 0; // 假 ID 用於試運行
                    }
                    $results['created']++;
                }

                // 處理依賴關係
                if ($options['validate_dependencies'] && !empty($dependencies)) {
                    $dependencyIds = Permission::whereIn('name', $dependencies)->pluck('id')->toArray();
                    $missingDependencies = array_diff($dependencies, Permission::whereIn('name', $dependencies)->pluck('name')->toArray());
                    
                    if (!empty($missingDependencies)) {
                        $results['warnings'][] = [
                            'permission' => $permissionData['name'],
                            'message' => '缺少依賴權限: ' . implode(', ', $missingDependencies),
                        ];
                    }
                    
                    if (!empty($dependencyIds) && !$options['dry_run'] && $permission->id) {
                        try {
                            $this->syncDependencies($permission, $dependencyIds);
                        } catch (\InvalidArgumentException $e) {
                            $results['warnings'][] = [
                                'permission' => $permissionData['name'],
                                'message' => '依賴關係設定失敗: ' . $e->getMessage(),
                            ];
                        }
                    }
                }
            }

            if ($options['dry_run']) {
                DB::rollBack();
            } else {
                DB::commit();
            }
            
            $results['success'] = empty($results['errors']);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * 批量處理權限操作（效能優化）
     * 
     * @param array $operations 操作陣列
     * @param int $batchSize 批次大小
     * @return array 處理結果
     */
    public function batchProcessPermissions(array $operations, int $batchSize = 100): array
    {
        $results = [
            'processed' => 0,
            'errors' => [],
            'success' => true,
        ];

        $chunks = array_chunk($operations, $batchSize);
        
        foreach ($chunks as $chunk) {
            DB::beginTransaction();
            
            try {
                foreach ($chunk as $operation) {
                    $this->processSingleOperation($operation);
                    $results['processed']++;
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $results['errors'][] = [
                    'chunk' => count($results['errors']) + 1,
                    'error' => $e->getMessage(),
                ];
                $results['success'] = false;
            }
        }

        return $results;
    }

    /**
     * 處理單個操作
     * 
     * @param array $operation 操作資料
     * @return void
     */
    private function processSingleOperation(array $operation): void
    {
        switch ($operation['type']) {
            case 'create':
                Permission::create($operation['data']);
                break;
            case 'update':
                Permission::where('id', $operation['id'])->update($operation['data']);
                break;
            case 'delete':
                Permission::where('id', $operation['id'])->delete();
                break;
            case 'sync_dependencies':
                $permission = Permission::find($operation['id']);
                if ($permission) {
                    $permission->dependencies()->sync($operation['dependency_ids']);
                }
                break;
        }
    }

    /**
     * 延遲載入權限關聯資料
     * 
     * @param Collection $permissions 權限集合
     * @param array $relations 要載入的關聯
     * @return Collection
     */
    public function lazyLoadPermissionRelations(Collection $permissions, array $relations = []): Collection
    {
        if ($permissions->isEmpty()) {
            return $permissions;
        }

        $permissionIds = $permissions->pluck('id')->toArray();

        // 批量載入角色關聯
        if (in_array('roles', $relations)) {
            $rolePermissions = DB::table('role_permissions as rp')
                                ->join('roles as r', 'rp.role_id', '=', 'r.id')
                                ->whereIn('rp.permission_id', $permissionIds)
                                ->select('rp.permission_id', 'r.id as role_id', 'r.name', 'r.display_name')
                                ->get()
                                ->groupBy('permission_id');

            foreach ($permissions as $permission) {
                $permission->setRelation('roles', $rolePermissions->get($permission->id, collect()));
            }
        }

        // 批量載入依賴關係
        if (in_array('dependencies', $relations)) {
            $dependencies = DB::table('permission_dependencies as pd')
                             ->join('permissions as p', 'pd.depends_on_permission_id', '=', 'p.id')
                             ->whereIn('pd.permission_id', $permissionIds)
                             ->select('pd.permission_id', 'p.id', 'p.name', 'p.display_name', 'p.module')
                             ->get()
                             ->groupBy('permission_id');

            foreach ($permissions as $permission) {
                $permission->setRelation('dependencies', $dependencies->get($permission->id, collect()));
            }
        }

        // 批量載入被依賴關係
        if (in_array('dependents', $relations)) {
            $dependents = DB::table('permission_dependencies as pd')
                           ->join('permissions as p', 'pd.permission_id', '=', 'p.id')
                           ->whereIn('pd.depends_on_permission_id', $permissionIds)
                           ->select('pd.depends_on_permission_id as permission_id', 'p.id', 'p.name', 'p.display_name', 'p.module')
                           ->get()
                           ->groupBy('permission_id');

            foreach ($permissions as $permission) {
                $permission->setRelation('dependents', $dependents->get($permission->id, collect()));
            }
        }

        return $permissions;
    }

    /**
     * 高效能權限搜尋（使用全文搜尋）
     * 
     * @param string $search 搜尋關鍵字
     * @param array $options 搜尋選項
     * @return Collection
     */
    public function fullTextSearchPermissions(string $search, array $options = []): Collection
    {
        $cacheKey = 'fulltext_search_' . md5($search . serialize($options));
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($search, $options) {
            $query = Permission::select(['id', 'name', 'display_name', 'module', 'type', 'description']);

            // 使用 MySQL 全文搜尋（如果支援）
            if ($this->supportsFullTextSearch()) {
                $query->whereRaw("MATCH(name, display_name, description) AGAINST(? IN BOOLEAN MODE)", [$search]);
            } else {
                // 降級到 LIKE 搜尋
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 應用篩選選項
            if (!empty($options['module'])) {
                $query->where('module', $options['module']);
            }
            
            if (!empty($options['type'])) {
                $query->where('type', $options['type']);
            }

            $limit = $options['limit'] ?? 50;
            return $query->limit($limit)->get();
        });
    }

    /**
     * 檢查是否支援全文搜尋
     * 
     * @return bool
     */
    private function supportsFullTextSearch(): bool
    {
        try {
            // 檢查是否存在全文索引
            $indexes = DB::select("SHOW INDEX FROM permissions WHERE Index_type = 'FULLTEXT'");
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 分頁查詢優化（使用游標分頁）
     * 
     * @param array $filters 篩選條件
     * @param int $limit 限制數量
     * @param int|null $cursor 游標位置
     * @return array
     */
    public function getCursorPaginatedPermissions(array $filters = [], int $limit = 25, ?int $cursor = null): array
    {
        $query = Permission::select(['id', 'name', 'display_name', 'module', 'type', 'created_at']);

        // 應用篩選條件
        $this->applyFilters($query, $filters);

        // 游標分頁
        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $permissions = $query->orderBy('id')
                            ->limit($limit + 1) // 多取一筆判斷是否有下一頁
                            ->get();

        $hasNextPage = $permissions->count() > $limit;
        if ($hasNextPage) {
            $permissions->pop(); // 移除多取的那筆
        }

        $nextCursor = $hasNextPage ? $permissions->last()->id : null;

        return [
            'data' => $permissions,
            'has_next_page' => $hasNextPage,
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * 應用查詢篩選條件
     * 
     * @param Builder $query 查詢建構器
     * @param array $filters 篩選條件
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

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
            }
        }
    }

    /**
     * 權限資料預載入優化
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @param array $includes 要預載入的資料
     * @return Collection
     */
    public function preloadPermissionData(array $permissionIds, array $includes = []): Collection
    {
        if (empty($permissionIds)) {
            return collect();
        }

        $cacheKey = 'preload_permissions_' . md5(implode(',', $permissionIds) . serialize($includes));
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($permissionIds, $includes) {
            $query = Permission::whereIn('id', $permissionIds);

            // 根據需要載入關聯資料
            $with = [];
            $withCount = [];

            if (in_array('roles', $includes)) {
                $with[] = 'roles:id,name,display_name';
                $withCount[] = 'roles';
            }

            if (in_array('dependencies', $includes)) {
                $with[] = 'dependencies:id,name,display_name,module';
                $withCount[] = 'dependencies';
            }

            if (in_array('dependents', $includes)) {
                $with[] = 'dependents:id,name,display_name,module';
                $withCount[] = 'dependents';
            }

            if (!empty($with)) {
                $query->with($with);
            }

            if (!empty($withCount)) {
                $query->withCount($withCount);
            }

            return $query->get()->keyBy('id');
        });
    }

    /**
     * 檢查權限是否可以刪除
     * 
     * @param Permission $permission 權限實例
     * @return bool
     */
    public function canDeletePermission(Permission $permission): bool
    {
        // 檢查是否被角色使用
        if ($permission->roles()->exists()) {
            return false;
        }

        // 檢查是否被其他權限依賴
        if ($permission->dependents()->exists()) {
            return false;
        }

        // 檢查是否為系統核心權限（可以根據需要定義）
        $systemPermissions = ['admin.access', 'system.manage'];
        if (in_array($permission->name, $systemPermissions)) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否有循環依賴
     * 
     * @param int $permissionId 權限 ID
     * @param array $dependencyIds 依賴權限 ID 陣列
     * @return bool
     */
    public function hasCircularDependency(int $permissionId, array $dependencyIds): bool
    {
        foreach ($dependencyIds as $dependencyId) {
            // 不能依賴自己
            if ($dependencyId === $permissionId) {
                return true;
            }

            // 檢查目標權限是否已經依賴此權限
            if ($this->checkDependencyPath($dependencyId, $permissionId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查依賴路徑
     * 
     * @param int $fromId 起始權限 ID
     * @param int $toId 目標權限 ID
     * @param array $visited 已訪問的權限 ID
     * @return bool
     */
    private function checkDependencyPath(int $fromId, int $toId, array $visited = []): bool
    {
        if (in_array($fromId, $visited)) {
            return false; // 避免無限迴圈
        }

        $visited[] = $fromId;

        $dependencies = DB::table('permission_dependencies')
                         ->where('permission_id', $fromId)
                         ->pluck('depends_on_permission_id')
                         ->toArray();

        foreach ($dependencies as $dependencyId) {
            if ($dependencyId === $toId) {
                return true;
            }

            if ($this->checkDependencyPath($dependencyId, $toId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得權限的完整依賴鏈
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getFullDependencyChain(int $permissionId): Collection
    {
        $permission = Permission::findOrFail($permissionId);
        return $permission->getAllDependencies();
    }

    /**
     * 取得權限的完整被依賴鏈
     * 
     * @param int $permissionId 權限 ID
     * @return Collection
     */
    public function getFullDependentChain(int $permissionId): Collection
    {
        $permission = Permission::findOrFail($permissionId);
        return $permission->getAllDependents();
    }

    /**
     * 根據模組和類型篩選權限
     * 
     * @param string|null $module 模組名稱
     * @param string|null $type 權限類型
     * @return Collection
     */
    public function getPermissionsByModuleAndType(?string $module = null, ?string $type = null): Collection
    {
        $query = Permission::query();

        if ($module) {
            $query->where('module', $module);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('module')
                    ->orderBy('type')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * 搜尋權限
     * 
     * @param string $search 搜尋關鍵字
     * @return Collection
     */
    public function searchPermissions(string $search): Collection
    {
        return Permission::where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orderBy('module')
                        ->orderBy('name')
                        ->get();
    }

    /**
     * 取得所有可用的模組
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableModules(): \Illuminate\Support\Collection
    {
        return Permission::distinct()
                        ->orderBy('module')
                        ->pluck('module');
    }

    /**
     * 取得所有可用的權限類型
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableTypes(): \Illuminate\Support\Collection
    {
        return Permission::distinct()
                        ->orderBy('type')
                        ->pluck('type');
    }

    // Interface methods implementation

    /**
     * 取得所有權限
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Permission::all();
    }

    /**
     * 分頁取得權限列表
     *
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->getPaginatedPermissions($filters, $perPage);
    }

    /**
     * 根據 ID 尋找權限
     *
     * @param int $id 權限 ID
     * @return Permission|null
     */
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * 根據 ID 尋找權限，找不到則拋出例外
     *
     * @param int $id 權限 ID
     * @return Permission
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Permission
    {
        return Permission::findOrFail($id);
    }

    /**
     * 根據名稱尋找權限
     *
     * @param string $name 權限名稱
     * @return Permission|null
     */
    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    /**
     * 建立新權限
     *
     * @param array $data 權限資料
     * @return Permission
     */
    public function create(array $data): Permission
    {
        return $this->createPermission($data);
    }

    /**
     * 更新權限
     *
     * @param Permission $permission 權限實例
     * @param array $data 更新資料
     * @return bool
     */
    public function update(Permission $permission, array $data): bool
    {
        return $this->updatePermission($permission, $data);
    }

    /**
     * 刪除權限
     *
     * @param Permission $permission 權限實例
     * @return bool
     * @throws \Exception
     */
    public function delete(Permission $permission): bool
    {
        return $this->deletePermission($permission);
    }

    /**
     * 取得權限及其角色
     *
     * @param int $id 權限 ID
     * @return Permission|null
     */
    public function findWithRoles(int $id): ?Permission
    {
        return Permission::with('roles')->find($id);
    }

    /**
     * 檢查權限名稱是否已存在
     *
     * @param string $name 權限名稱
     * @param int|null $excludeId 排除的權限 ID
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = Permission::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 根據模組取得權限
     *
     * @param string $module 模組名稱
     * @return Collection
     */
    public function getByModule(string $module): Collection
    {
        return Permission::where('module', $module)->get();
    }

    /**
     * 取得所有模組列表
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllModules(): \Illuminate\Support\Collection
    {
        return Permission::distinct()->pluck('module')->sort()->values();
    }

    /**
     * 取得權限按模組分組
     *
     * @return Collection
     */
    public function getAllGroupedByModule(): Collection
    {
        return $this->getPermissionsByModule();
    }

    /**
     * 搜尋權限
     *
     * @param string $term 搜尋關鍵字
     * @param int $limit 限制筆數
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return $this->searchPermissionsOptimized($term, [], $limit);
    }

    /**
     * 批量建立權限
     *
     * @param array $permissions 權限資料陣列
     * @return bool
     * @throws \Exception
     */
    public function bulkCreate(array $permissions): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($permissions as $permissionData) {
                Permission::create($permissionData);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 批量刪除權限
     *
     * @param array $permissionIds 權限 ID 陣列
     * @return int 刪除的記錄數
     * @throws \Exception
     */
    public function bulkDelete(array $permissionIds): int
    {
        try {
            DB::beginTransaction();
            
            // 移除角色關聯
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            
            // 移除依賴關係
            DB::table('permission_dependencies')
                ->whereIn('permission_id', $permissionIds)
                ->orWhereIn('depends_on_permission_id', $permissionIds)
                ->delete();
            
            // 刪除權限
            $deleted = Permission::whereIn('id', $permissionIds)->delete();
            
            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取得權限統計資訊
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->getPermissionUsageStats();
    }

    /**
     * 取得模組的權限樹狀結構
     *
     * @param string $module 模組名稱
     * @return array
     */
    public function getModulePermissionTree(string $module): array
    {
        $permissions = Permission::where('module', $module)
                                ->with(['dependencies', 'dependents'])
                                ->get();
        
        return $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'dependencies' => $permission->dependencies->pluck('name')->toArray(),
                'dependents' => $permission->dependents->pluck('name')->toArray(),
            ];
        })->toArray();
    }

    /**
     * 取得權限矩陣（所有模組和權限的結構化資料）
     *
     * @return array
     */
    public function getPermissionMatrix(): array
    {
        $permissions = $this->getPermissionsByModule();
        $roles = Role::with('permissions')->get();
        
        return [
            'permissions' => $permissions,
            'roles' => $roles,
            'matrix' => $roles->map(function ($role) use ($permissions) {
                return [
                    'role' => $role,
                    'permissions' => $permissions->flatten()->map(function ($permission) use ($role) {
                        return [
                            'permission' => $permission,
                            'has_permission' => $role->permissions->contains('id', $permission->id)
                        ];
                    })
                ];
            })
        ];
    }

    /**
     * 根據角色 ID 取得權限
     *
     * @param int $roleId 角色 ID
     * @return Collection
     */
    public function getByRoleId(int $roleId): Collection
    {
        return Permission::whereHas('roles', function ($query) use ($roleId) {
            $query->where('roles.id', $roleId);
        })->get();
    }

    /**
     * 取得未分配給任何角色的權限
     *
     * @return Collection
     */
    public function getUnassignedPermissions(): Collection
    {
        return $this->getUnusedPermissions();
    }

    /**
     * 解析權限依賴關係
     *
     * @param array $permissionIds 權限 ID 陣列
     * @return array 包含依賴關係的權限 ID 陣列
     */
    public function resolveDependencies(array $permissionIds): array
    {
        $resolved = [];
        $toProcess = $permissionIds;
        
        while (!empty($toProcess)) {
            $currentId = array_shift($toProcess);
            
            if (in_array($currentId, $resolved)) {
                continue;
            }
            
            $resolved[] = $currentId;
            
            $dependencies = $this->getPermissionDependencies($currentId);
            foreach ($dependencies as $dependency) {
                if (!in_array($dependency->id, $resolved) && !in_array($dependency->id, $toProcess)) {
                    $toProcess[] = $dependency->id;
                }
            }
        }
        
        return $resolved;
    }

    /**
     * 取得權限的依賴權限
     *
     * @param Permission $permission 權限實例
     * @return Collection
     */
    public function getDependencies(Permission $permission): Collection
    {
        return $this->getPermissionDependencies($permission->id);
    }

    /**
     * 取得依賴於指定權限的其他權限
     *
     * @param Permission $permission 權限實例
     * @return Collection
     */
    public function getDependents(Permission $permission): Collection
    {
        return $this->getPermissionDependents($permission->id);
    }

    /**
     * 檢查權限是否可以被刪除（沒有其他權限依賴它）
     *
     * @param Permission $permission 權限實例
     * @return bool
     */
    public function canBeDeleted(Permission $permission): bool
    {
        return $this->canDeletePermission($permission);
    }

    /**
     * 取得權限的角色數量
     *
     * @param Permission $permission 權限實例
     * @return int
     */
    public function getRoleCount(Permission $permission): int
    {
        return $permission->roles()->count();
    }

    /**
     * 驗證權限組合的有效性
     *
     * @param array $permissionIds 權限 ID 陣列
     * @return array 驗證結果，包含錯誤訊息
     */
    public function validatePermissionCombination(array $permissionIds): array
    {
        $errors = [];
        
        foreach ($permissionIds as $permissionId) {
            $dependencies = $this->getPermissionDependencies($permissionId);
            
            foreach ($dependencies as $dependency) {
                if (!in_array($dependency->id, $permissionIds)) {
                    $permission = Permission::find($permissionId);
                    $errors[] = "權限 '{$permission->display_name}' 需要依賴權限 '{$dependency->display_name}'";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}