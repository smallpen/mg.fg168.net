<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 權限模型
 * 
 * 管理系統權限和角色關聯
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'type',
    ];

    /**
     * 權限屬於的角色關聯
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
                    ->withTimestamps();
    }

    /**
     * 根據模組分組權限
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function groupedByModule()
    {
        return static::all()->groupBy('module');
    }

    /**
     * 取得特定模組的權限
     * 
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByModule(string $module)
    {
        return static::where('module', $module)->get();
    }

    /**
     * 檢查權限是否被任何角色使用
     * 
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->roles()->exists();
    }

    /**
     * 取得使用此權限的角色數量
     * 
     * @return int
     */
    public function getRoleCountAttribute(): int
    {
        return $this->roles()->count();
    }

    /**
     * 此權限依賴的其他權限
     * 
     * @return BelongsToMany
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_dependencies',
            'permission_id',
            'depends_on_permission_id'
        )->withTimestamps();
    }

    /**
     * 依賴此權限的其他權限
     * 
     * @return BelongsToMany
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_dependencies',
            'depends_on_permission_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * 檢查此權限是否依賴於另一個權限
     * 
     * @param Permission|string $permission
     * @return bool
     */
    public function dependsOn($permission): bool
    {
        if (is_string($permission)) {
            return $this->dependencies()->where('name', $permission)->exists();
        }
        
        return $this->dependencies()->where('permissions.id', $permission->id)->exists();
    }





    /**
     * 取得權限的本地化顯示名稱
     * 
     * @return string
     */
    public function getLocalizedDisplayNameAttribute(): string
    {
        $langKey = "admin.permissions.names.{$this->name}";
        $translated = __($langKey);
        
        if ($translated !== $langKey) {
            return $translated;
        }
        
        return $this->display_name;
    }

    /**
     * 取得權限的本地化描述
     * 
     * @return string
     */
    public function getLocalizedDescriptionAttribute(): string
    {
        $langKey = "admin.permissions.descriptions.{$this->name}";
        $translated = __($langKey);
        
        if ($translated !== $langKey) {
            return $translated;
        }
        
        return $this->description ?? '';
    }

    /**
     * 檢查權限是否有依賴
     * 
     * @return bool
     */
    public function hasDependencies(): bool
    {
        return $this->dependencies()->exists();
    }

    /**
     * 檢查權限是否被其他權限依賴
     * 
     * @return bool
     */
    public function hasDependents(): bool
    {
        return $this->dependents()->exists();
    }

    /**
     * 取得使用此權限的使用者數量（透過角色）
     * 
     * @return int
     */
    public function getUserCountAttribute(): int
    {
        return Cache::remember("permission_user_count_{$this->id}", 1800, function () {
            return $this->roles()
                       ->with('users')
                       ->get()
                       ->pluck('users')
                       ->flatten()
                       ->unique('id')
                       ->count();
        });
    }

    /**
     * 檢查是否為系統核心權限
     * 
     * @return bool
     */
    public function getIsSystemPermissionAttribute(): bool
    {
        // 系統核心權限通常以 'system.' 開頭或屬於核心模組
        $systemModules = ['system', 'auth', 'admin'];
        $systemPrefixes = ['system.', 'auth.', 'admin.core'];
        
        foreach ($systemPrefixes as $prefix) {
            if (str_starts_with($this->name, $prefix)) {
                return true;
            }
        }
        
        return in_array($this->module, $systemModules);
    }

    /**
     * 檢查權限是否可以被刪除
     * 
     * @return bool
     */
    public function getCanBeDeletedAttribute(): bool
    {
        // 系統權限不能刪除
        if ($this->is_system_permission) {
            return false;
        }
        
        // 被角色使用的權限不能刪除
        if ($this->isInUse()) {
            return false;
        }
        
        // 被其他權限依賴的權限不能刪除
        if ($this->hasDependents()) {
            return false;
        }
        
        return true;
    }

    /**
     * 取得權限使用統計資料
     * 
     * @return array
     */
    public function getUsageStatsAttribute(): array
    {
        return Cache::remember("permission_usage_stats_{$this->id}", 3600, function () {
            $roleCount = $this->role_count;
            $userCount = $this->user_count;
            
            return [
                'role_count' => $roleCount,
                'user_count' => $userCount,
                'is_used' => $roleCount > 0,
                'usage_frequency' => $this->getUsageFrequency(),
                'last_used_at' => $this->getLastUsedAt(),
                'dependency_count' => $this->dependencies()->count(),
                'dependent_count' => $this->dependents()->count(),
                'usage_intensity' => $this->getUsageIntensity(),
                'frequency_level' => $this->getFrequencyLevel(),
                'is_marked_unused' => $this->isMarkedAsUnused(),
            ];
        });
    }

    /**
     * 取得使用強度
     * 
     * @return float
     */
    public function getUsageIntensity(): float
    {
        $roleCount = $this->role_count;
        $userCount = $this->user_count;
        
        // 使用強度 = (使用者數量 * 2 + 角色數量) / 10
        return round(($userCount * 2 + $roleCount) / 10, 2);
    }

    /**
     * 取得頻率等級
     * 
     * @return string
     */
    public function getFrequencyLevel(): string
    {
        $frequency = $this->getUsageFrequency();
        
        if ($frequency >= 50) return 'very_high';
        if ($frequency >= 20) return 'high';
        if ($frequency >= 10) return 'medium';
        if ($frequency >= 5) return 'low';
        return 'very_low';
    }

    /**
     * 檢查是否被標記為未使用
     * 
     * @return bool
     */
    public function isMarkedAsUnused(): bool
    {
        $markedPermissions = Cache::get('marked_unused_permissions', []);
        return in_array($this->id, $markedPermissions);
    }

    /**
     * 標記為未使用
     * 
     * @return void
     */
    public function markAsUnused(): void
    {
        $markedPermissions = Cache::get('marked_unused_permissions', []);
        if (!in_array($this->id, $markedPermissions)) {
            $markedPermissions[] = $this->id;
            Cache::put('marked_unused_permissions', $markedPermissions, now()->addDays(30));
        }
    }

    /**
     * 取消未使用標記
     * 
     * @return void
     */
    public function unmarkAsUnused(): void
    {
        $markedPermissions = Cache::get('marked_unused_permissions', []);
        $markedPermissions = array_diff($markedPermissions, [$this->id]);
        Cache::put('marked_unused_permissions', $markedPermissions, now()->addDays(30));
    }

    /**
     * 取得使用趨勢資料
     * 
     * @param int $days
     * @return array
     */
    public function getUsageTrend(int $days = 30): array
    {
        return Cache::remember("permission_trend_{$this->id}_{$days}", 7200, function () use ($days) {
            $startDate = Carbon::now()->subDays($days);
            
            // 從活動日誌取得使用趨勢
            $dailyUsage = DB::table('activity_log')
                           ->where('subject_type', static::class)
                           ->where('subject_id', $this->id)
                           ->where('created_at', '>=', $startDate)
                           ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                           ->groupBy('date')
                           ->orderBy('date')
                           ->get()
                           ->keyBy('date')
                           ->map(function ($item) {
                               return (int) $item->count;
                           });
            
            // 填補缺失的日期
            $trend = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $trend[$date] = $dailyUsage->get($date, 0);
            }
            
            return [
                'daily_usage' => $trend,
                'total_usage' => array_sum($trend),
                'average_daily' => round(array_sum($trend) / $days, 2),
                'peak_usage' => max($trend),
                'trend_direction' => $this->calculateTrendDirection($trend),
            ];
        });
    }

    /**
     * 計算趨勢方向
     * 
     * @param array $trend
     * @return string
     */
    private function calculateTrendDirection(array $trend): string
    {
        $values = array_values($trend);
        $count = count($values);
        
        if ($count < 2) return 'stable';
        
        $firstHalf = array_slice($values, 0, intval($count / 2));
        $secondHalf = array_slice($values, intval($count / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $difference = $secondAvg - $firstAvg;
        
        if ($difference > 1) return 'increasing';
        if ($difference < -1) return 'decreasing';
        return 'stable';
    }

    /**
     * 新增權限依賴（改進版本，包含循環依賴檢查）
     * 
     * @param Permission|int $permission
     * @return void
     * @throws \InvalidArgumentException
     */
    public function addDependency($permission): void
    {
        $permissionId = $permission instanceof Permission ? $permission->id : $permission;
        
        // 檢查循環依賴
        if (!PermissionDependency::validateNoCycle($this->id, $permissionId)) {
            throw new \InvalidArgumentException('不能建立循環依賴關係');
        }
        
        // 檢查是否已存在依賴關係
        if (!$this->dependencies()->where('permissions.id', $permissionId)->exists()) {
            $this->dependencies()->attach($permissionId);
            $this->clearDependencyCache();
        }
    }

    /**
     * 移除權限依賴（改進版本）
     * 
     * @param Permission|int $permission
     * @return void
     */
    public function removeDependency($permission): void
    {
        $permissionId = $permission instanceof Permission ? $permission->id : $permission;
        
        $this->dependencies()->detach($permissionId);
        $this->clearDependencyCache();
    }

    /**
     * 同步權限依賴（改進版本，包含循環依賴檢查）
     * 
     * @param array $permissionIds
     * @return void
     * @throws \InvalidArgumentException
     */
    public function syncDependencies(array $permissionIds): void
    {
        // 檢查每個依賴是否會造成循環依賴
        foreach ($permissionIds as $permissionId) {
            if (!PermissionDependency::validateNoCycle($this->id, $permissionId)) {
                throw new \InvalidArgumentException("權限 ID {$permissionId} 會造成循環依賴");
            }
        }
        
        $this->dependencies()->sync($permissionIds);
        $this->clearDependencyCache();
    }

    /**
     * 取得所有依賴此權限的權限（遞迴，使用快取）
     * 
     * @return Collection
     */
    public function getAllDependents(): Collection
    {
        return Cache::remember("permission_all_dependents_{$this->id}", 1800, function () {
            return $this->resolveDependentChain();
        });
    }

    /**
     * 取得此權限依賴的所有權限（遞迴，使用快取）
     * 
     * @return Collection
     */
    public function getAllDependencies(): Collection
    {
        return Cache::remember("permission_all_dependencies_{$this->id}", 1800, function () {
            return $this->resolveDependencyChain();
        });
    }

    /**
     * 檢查是否有循環依賴（改進版本）
     * 
     * @param array $dependencyIds
     * @return bool
     */
    public function hasCircularDependency(array $dependencyIds): bool
    {
        foreach ($dependencyIds as $dependencyId) {
            if (!PermissionDependency::validateNoCycle($this->id, $dependencyId)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 檢查權限是否被使用
     * 
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->isInUse();
    }

    /**
     * 取得權限使用頻率
     * 
     * @return int
     */
    public function getUsageFrequency(): int
    {
        // 這裡可以根據實際需求實作，例如從審計日誌計算
        // 目前簡單地以角色數量作為使用頻率指標
        return $this->role_count;
    }

    /**
     * 取得權限最後使用時間
     * 
     * @return Carbon|null
     */
    public function getLastUsedAt(): ?Carbon
    {
        // 這裡可以根據實際需求實作，例如從審計日誌取得
        // 目前返回最近的角色關聯時間
        $latestRoleAssignment = $this->roles()
                                   ->orderBy('role_permissions.created_at', 'desc')
                                   ->first();
        
        return $latestRoleAssignment ? 
               Carbon::parse($latestRoleAssignment->pivot->created_at) : 
               null;
    }

    /**
     * 解析依賴鏈（私有方法）
     * 
     * @param array $visited
     * @return Collection
     */
    private function resolveDependencyChain(array $visited = []): Collection
    {
        if (in_array($this->id, $visited)) {
            return collect();
        }
        
        $visited[] = $this->id;
        $allDependencies = collect();
        $directDependencies = $this->dependencies;
        
        foreach ($directDependencies as $dependency) {
            $allDependencies->push($dependency);
            $allDependencies = $allDependencies->merge(
                $dependency->resolveDependencyChain($visited)
            );
        }
        
        return $allDependencies->unique('id');
    }

    /**
     * 解析依賴者鏈（私有方法）
     * 
     * @param array $visited
     * @return Collection
     */
    private function resolveDependentChain(array $visited = []): Collection
    {
        if (in_array($this->id, $visited)) {
            return collect();
        }
        
        $visited[] = $this->id;
        $allDependents = collect();
        $directDependents = $this->dependents;
        
        foreach ($directDependents as $dependent) {
            $allDependents->push($dependent);
            $allDependents = $allDependents->merge(
                $dependent->resolveDependentChain($visited)
            );
        }
        
        return $allDependents->unique('id');
    }

    /**
     * 清除依賴關係快取
     * 
     * @return void
     */
    private function clearDependencyCache(): void
    {
        Cache::forget("permission_all_dependencies_{$this->id}");
        Cache::forget("permission_all_dependents_{$this->id}");
        Cache::forget("permission_usage_stats_{$this->id}");
        Cache::forget("permission_user_count_{$this->id}");
    }

    /**
     * 批量載入權限依賴關係（效能優化）
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return array 依賴關係映射
     */
    public static function batchLoadDependencies(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        sort($permissionIds);
        $cacheKey = 'batch_dependencies_' . md5(implode(',', $permissionIds));
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionIds) {
            $dependencies = DB::table('permission_dependencies as pd')
                             ->join('permissions as p', 'pd.depends_on_permission_id', '=', 'p.id')
                             ->whereIn('pd.permission_id', $permissionIds)
                             ->select('pd.permission_id', 'p.id as dependency_id', 'p.name', 'p.display_name', 'p.module')
                             ->get()
                             ->groupBy('permission_id');

            $result = [];
            foreach ($permissionIds as $permissionId) {
                $result[$permissionId] = $dependencies->get($permissionId, collect())->toArray();
            }

            return $result;
        });
    }

    /**
     * 批量載入權限被依賴關係（效能優化）
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return array 被依賴關係映射
     */
    public static function batchLoadDependents(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        sort($permissionIds);
        $cacheKey = 'batch_dependents_' . md5(implode(',', $permissionIds));
        
        return Cache::remember($cacheKey, 1800, function () use ($permissionIds) {
            $dependents = DB::table('permission_dependencies as pd')
                           ->join('permissions as p', 'pd.permission_id', '=', 'p.id')
                           ->whereIn('pd.depends_on_permission_id', $permissionIds)
                           ->select('pd.depends_on_permission_id as permission_id', 'p.id as dependent_id', 'p.name', 'p.display_name', 'p.module')
                           ->get()
                           ->groupBy('permission_id');

            $result = [];
            foreach ($permissionIds as $permissionId) {
                $result[$permissionId] = $dependents->get($permissionId, collect())->toArray();
            }

            return $result;
        });
    }

    /**
     * 批量檢查權限使用狀態（效能優化）
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return array 使用狀態映射
     */
    public static function batchCheckUsageStatus(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        sort($permissionIds);
        $cacheKey = 'batch_usage_status_' . md5(implode(',', $permissionIds));
        
        return Cache::remember($cacheKey, 900, function () use ($permissionIds) {
            $usageStats = DB::table('role_permissions')
                           ->select('permission_id', DB::raw('COUNT(role_id) as role_count'))
                           ->whereIn('permission_id', $permissionIds)
                           ->groupBy('permission_id')
                           ->pluck('role_count', 'permission_id');

            $result = [];
            foreach ($permissionIds as $permissionId) {
                $roleCount = $usageStats->get($permissionId, 0);
                $result[$permissionId] = [
                    'is_used' => $roleCount > 0,
                    'role_count' => $roleCount,
                    'usage_level' => static::getUsageLevelFromCount($roleCount),
                ];
            }

            return $result;
        });
    }

    /**
     * 根據角色數量取得使用等級
     * 
     * @param int $roleCount 角色數量
     * @return string
     */
    private static function getUsageLevelFromCount(int $roleCount): string
    {
        if ($roleCount >= 10) return 'very_high';
        if ($roleCount >= 5) return 'high';
        if ($roleCount >= 2) return 'medium';
        if ($roleCount >= 1) return 'low';
        return 'unused';
    }

    /**
     * 高效能搜尋權限
     * 
     * @param string $search 搜尋關鍵字
     * @param array $options 搜尋選項
     * @return Collection
     */
    public static function searchOptimized(string $search, array $options = []): Collection
    {
        $cacheKey = 'permission_search_optimized_' . md5($search . serialize($options));
        
        return Cache::remember($cacheKey, 600, function () use ($search, $options) {
            $query = static::select(['id', 'name', 'display_name', 'module', 'type', 'description']);

            // 優化搜尋邏輯
            $query->where(function ($q) use ($search) {
                // 精確匹配權重最高
                $q->where('name', $search)
                  ->orWhere('display_name', $search)
                  // 前綴匹配次之
                  ->orWhere('name', 'like', "{$search}%")
                  ->orWhere('display_name', 'like', "{$search}%")
                  // 模糊匹配權重最低
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
                
                // 只有搜尋詞較長時才搜尋描述
                if (strlen($search) >= 3) {
                    $q->orWhere('description', 'like', "%{$search}%");
                }
            });

            // 應用篩選選項
            if (!empty($options['module'])) {
                $query->where('module', $options['module']);
            }
            
            if (!empty($options['type'])) {
                $query->where('type', $options['type']);
            }
            
            if (!empty($options['usage_status'])) {
                switch ($options['usage_status']) {
                    case 'used':
                        $query->has('roles');
                        break;
                    case 'unused':
                        $query->doesntHave('roles');
                        break;
                }
            }

            // 按相關性排序
            $query->orderByRaw("
                CASE 
                    WHEN name = ? THEN 1
                    WHEN display_name = ? THEN 2
                    WHEN name LIKE ? THEN 3
                    WHEN display_name LIKE ? THEN 4
                    ELSE 5
                END
            ", [$search, $search, "{$search}%", "{$search}%"])
            ->orderBy('module')
            ->orderBy('name');

            $limit = $options['limit'] ?? 50;
            return $query->limit($limit)->get();
        });
    }

    /**
     * 取得權限統計摘要（效能優化）
     * 
     * @return array
     */
    public static function getStatsSummary(): array
    {
        return Cache::remember('permission_stats_summary', 3600, function () {
            $totalPermissions = static::count();
            $usedPermissions = static::has('roles')->count();
            $systemPermissions = static::where(function ($query) {
                $query->where('module', 'system')
                      ->orWhere('module', 'auth')
                      ->orWhere('module', 'admin')
                      ->orWhere('name', 'like', 'system.%')
                      ->orWhere('name', 'like', 'auth.%')
                      ->orWhere('name', 'like', 'admin.core%');
            })->count();

            $moduleStats = static::select('module')
                                ->withCount('roles')
                                ->get()
                                ->groupBy('module')
                                ->map(function ($permissions, $module) {
                                    $total = $permissions->count();
                                    $used = $permissions->where('roles_count', '>', 0)->count();
                                    
                                    return [
                                        'total' => $total,
                                        'used' => $used,
                                        'unused' => $total - $used,
                                        'usage_rate' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
                                    ];
                                });

            return [
                'total_permissions' => $totalPermissions,
                'used_permissions' => $usedPermissions,
                'unused_permissions' => $totalPermissions - $usedPermissions,
                'system_permissions' => $systemPermissions,
                'custom_permissions' => $totalPermissions - $systemPermissions,
                'overall_usage_rate' => $totalPermissions > 0 ? 
                                      round(($usedPermissions / $totalPermissions) * 100, 2) : 0,
                'modules' => $moduleStats,
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * 清除所有相關快取（效能優化版本）
     * 
     * @return void
     */
    public function clearAllRelatedCache(): void
    {
        // 清除特定權限的快取
        $this->clearDependencyCache();
        
        // 清除批量快取
        Cache::tags(['permissions', 'permission_dependencies', 'role_permissions'])->flush();
        
        // 清除統計快取
        Cache::forget('permission_stats_summary');
        Cache::forget('permissions_by_module_optimized');
        
        // 清除搜尋快取（這裡簡化處理，實際可以更精確）
        // 在生產環境中，可以使用更精確的快取標籤管理
    }

    /**
     * 模型事件：刪除時清除快取（效能優化版本）
     */
    protected static function booted()
    {
        static::created(function ($permission) {
            $permission->clearAllRelatedCache();
        });
        
        static::updated(function ($permission) {
            $permission->clearAllRelatedCache();
        });
        
        static::deleted(function ($permission) {
            $permission->clearAllRelatedCache();
        });
        
        // 批量操作時的快取清除
        static::saved(function ($permission) {
            // 延遲清除快取，避免在批量操作時頻繁清除
            Cache::put('permission_cache_needs_clear', true, now()->addMinutes(5));
        });
    }
}