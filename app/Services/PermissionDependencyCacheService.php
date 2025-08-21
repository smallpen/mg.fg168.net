<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionDependency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 權限依賴關係快取服務
 * 
 * 專門處理權限依賴關係的快取和優化
 */
class PermissionDependencyCacheService
{
    /**
     * 快取標籤
     */
    const CACHE_TAG_DEPENDENCIES = 'permission_dependencies';
    const CACHE_TAG_DEPENDENCY_TREE = 'dependency_tree';
    const CACHE_TAG_CIRCULAR_CHECK = 'circular_dependency_check';

    /**
     * 快取時間
     */
    const CACHE_TTL_SHORT = 900;    // 15 分鐘
    const CACHE_TTL_MEDIUM = 3600;  // 1 小時
    const CACHE_TTL_LONG = 7200;    // 2 小時

    /**
     * 取得權限的完整依賴樹（包含遞迴依賴）
     * 
     * @param int $permissionId 權限 ID
     * @param bool $includeReverse 是否包含反向依賴
     * @return array
     */
    public function getFullDependencyTree(int $permissionId, bool $includeReverse = false): array
    {
        $cacheKey = "full_dependency_tree_{$permissionId}_" . ($includeReverse ? 'with_reverse' : 'forward_only');
        
        return Cache::tags([self::CACHE_TAG_DEPENDENCY_TREE])
                   ->remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($permissionId, $includeReverse) {
                       $tree = [
                           'permission_id' => $permissionId,
                           'dependencies' => $this->buildDependencyTree($permissionId, 'forward'),
                           'max_depth' => 0,
                           'total_dependencies' => 0,
                       ];

                       if ($includeReverse) {
                           $tree['dependents'] = $this->buildDependencyTree($permissionId, 'reverse');
                           $tree['total_dependents'] = 0;
                       }

                       // 計算統計資訊
                       $tree['max_depth'] = $this->calculateMaxDepth($tree['dependencies']);
                       $tree['total_dependencies'] = $this->countTotalNodes($tree['dependencies']);
                       
                       if ($includeReverse) {
                           $tree['total_dependents'] = $this->countTotalNodes($tree['dependents']);
                       }

                       return $tree;
                   });
    }

    /**
     * 建立依賴樹結構
     * 
     * @param int $permissionId 權限 ID
     * @param string $direction 方向 (forward|reverse)
     * @param array $visited 已訪問的節點（避免循環）
     * @param int $depth 當前深度
     * @return array
     */
    private function buildDependencyTree(int $permissionId, string $direction = 'forward', array $visited = [], int $depth = 0): array
    {
        // 防止無限遞迴
        if (in_array($permissionId, $visited) || $depth > 10) {
            return [];
        }

        $visited[] = $permissionId;
        $tree = [];

        // 根據方向取得直接依賴或被依賴
        $relations = $this->getDirectRelations($permissionId, $direction);

        foreach ($relations as $relation) {
            $relatedId = $direction === 'forward' ? $relation->depends_on_permission_id : $relation->permission_id;
            
            $node = [
                'permission_id' => $relatedId,
                'permission' => $this->getPermissionBasicInfo($relatedId),
                'depth' => $depth + 1,
                'children' => $this->buildDependencyTree($relatedId, $direction, $visited, $depth + 1),
            ];

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * 取得直接關聯關係
     * 
     * @param int $permissionId 權限 ID
     * @param string $direction 方向
     * @return Collection
     */
    private function getDirectRelations(int $permissionId, string $direction): Collection
    {
        $cacheKey = "direct_relations_{$permissionId}_{$direction}";
        
        return Cache::tags([self::CACHE_TAG_DEPENDENCIES])
                   ->remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($permissionId, $direction) {
                       $query = PermissionDependency::query();
                       
                       if ($direction === 'forward') {
                           $query->where('permission_id', $permissionId);
                       } else {
                           $query->where('depends_on_permission_id', $permissionId);
                       }
                       
                       return $query->get();
                   });
    }

    /**
     * 取得權限基本資訊
     * 
     * @param int $permissionId 權限 ID
     * @return array|null
     */
    private function getPermissionBasicInfo(int $permissionId): ?array
    {
        $cacheKey = "permission_basic_info_{$permissionId}";
        
        return Cache::tags([self::CACHE_TAG_DEPENDENCIES])
                   ->remember($cacheKey, self::CACHE_TTL_LONG, function () use ($permissionId) {
                       $permission = Permission::select(['id', 'name', 'display_name', 'module', 'type'])
                                              ->find($permissionId);
                       
                       return $permission ? $permission->toArray() : null;
                   });
    }

    /**
     * 計算樹的最大深度
     * 
     * @param array $tree 樹結構
     * @return int
     */
    private function calculateMaxDepth(array $tree): int
    {
        if (empty($tree)) {
            return 0;
        }

        $maxDepth = 0;
        foreach ($tree as $node) {
            $nodeDepth = $node['depth'] + $this->calculateMaxDepth($node['children']);
            $maxDepth = max($maxDepth, $nodeDepth);
        }

        return $maxDepth;
    }

    /**
     * 計算樹中的總節點數
     * 
     * @param array $tree 樹結構
     * @return int
     */
    private function countTotalNodes(array $tree): int
    {
        $count = count($tree);
        
        foreach ($tree as $node) {
            $count += $this->countTotalNodes($node['children']);
        }

        return $count;
    }

    /**
     * 批量檢查循環依賴
     * 
     * @param array $dependencyPairs 依賴對陣列 [[permission_id, depends_on_id], ...]
     * @return array 檢查結果
     */
    public function batchCheckCircularDependencies(array $dependencyPairs): array
    {
        $cacheKey = 'batch_circular_check_' . md5(serialize($dependencyPairs));
        
        return Cache::tags([self::CACHE_TAG_CIRCULAR_CHECK])
                   ->remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($dependencyPairs) {
                       $results = [];
                       
                       foreach ($dependencyPairs as $pair) {
                           [$permissionId, $dependsOnId] = $pair;
                           $results[] = [
                               'permission_id' => $permissionId,
                               'depends_on_id' => $dependsOnId,
                               'has_circular' => $this->checkSingleCircularDependency($permissionId, $dependsOnId),
                               'path' => $this->findCircularPath($permissionId, $dependsOnId),
                           ];
                       }
                       
                       return $results;
                   });
    }

    /**
     * 檢查單個循環依賴
     * 
     * @param int $permissionId 權限 ID
     * @param int $dependsOnId 依賴的權限 ID
     * @return bool
     */
    private function checkSingleCircularDependency(int $permissionId, int $dependsOnId): bool
    {
        // 不能依賴自己
        if ($permissionId === $dependsOnId) {
            return true;
        }

        // 檢查是否存在從 dependsOnId 到 permissionId 的路徑
        return $this->hasPath($dependsOnId, $permissionId);
    }

    /**
     * 檢查是否存在從起點到終點的依賴路徑
     * 
     * @param int $fromId 起點權限 ID
     * @param int $toId 終點權限 ID
     * @param array $visited 已訪問的節點
     * @param int $maxDepth 最大搜尋深度
     * @return bool
     */
    private function hasPath(int $fromId, int $toId, array $visited = [], int $maxDepth = 10): bool
    {
        if (in_array($fromId, $visited) || $maxDepth <= 0) {
            return false;
        }

        $visited[] = $fromId;

        // 取得直接依賴
        $directDependencies = $this->getDirectRelations($fromId, 'forward');
        
        foreach ($directDependencies as $dependency) {
            $dependsOnId = $dependency->depends_on_permission_id;
            
            if ($dependsOnId === $toId) {
                return true;
            }
            
            if ($this->hasPath($dependsOnId, $toId, $visited, $maxDepth - 1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 找出循環依賴路徑
     * 
     * @param int $permissionId 權限 ID
     * @param int $dependsOnId 依賴的權限 ID
     * @return array|null
     */
    private function findCircularPath(int $permissionId, int $dependsOnId): ?array
    {
        if (!$this->checkSingleCircularDependency($permissionId, $dependsOnId)) {
            return null;
        }

        // 使用廣度優先搜尋找出最短循環路徑
        $queue = [[$dependsOnId]];
        $visited = [];

        while (!empty($queue)) {
            $path = array_shift($queue);
            $currentId = end($path);

            if (in_array($currentId, $visited)) {
                continue;
            }

            $visited[] = $currentId;

            $directDependencies = $this->getDirectRelations($currentId, 'forward');
            
            foreach ($directDependencies as $dependency) {
                $nextId = $dependency->depends_on_permission_id;
                $newPath = array_merge($path, [$nextId]);

                if ($nextId === $permissionId) {
                    // 找到循環路徑
                    return $this->enrichPathWithPermissionInfo($newPath);
                }

                if (count($newPath) < 10) { // 限制路徑長度
                    $queue[] = $newPath;
                }
            }
        }

        return null;
    }

    /**
     * 為路徑添加權限資訊
     * 
     * @param array $path 權限 ID 路徑
     * @return array
     */
    private function enrichPathWithPermissionInfo(array $path): array
    {
        $enrichedPath = [];
        
        foreach ($path as $permissionId) {
            $info = $this->getPermissionBasicInfo($permissionId);
            $enrichedPath[] = [
                'id' => $permissionId,
                'name' => $info['name'] ?? 'Unknown',
                'display_name' => $info['display_name'] ?? 'Unknown',
                'module' => $info['module'] ?? 'Unknown',
            ];
        }

        return $enrichedPath;
    }

    /**
     * 取得依賴關係統計
     * 
     * @return array
     */
    public function getDependencyStatistics(): array
    {
        return Cache::tags([self::CACHE_TAG_DEPENDENCIES])
                   ->remember('dependency_statistics', self::CACHE_TTL_MEDIUM, function () {
                       $stats = [
                           'total_dependencies' => PermissionDependency::count(),
                           'permissions_with_dependencies' => Permission::has('dependencies')->count(),
                           'permissions_being_depended' => Permission::has('dependents')->count(),
                           'isolated_permissions' => Permission::doesntHave('dependencies')
                                                              ->doesntHave('dependents')
                                                              ->count(),
                           'complex_permissions' => [],
                           'circular_dependencies' => [],
                           'dependency_depth_distribution' => [],
                       ];

                       // 找出複雜的權限（依賴關係較多）
                       $stats['complex_permissions'] = Permission::withCount(['dependencies', 'dependents'])
                                                                ->having('dependencies_count', '>=', 3)
                                                                ->orHaving('dependents_count', '>=', 3)
                                                                ->orderBy('dependencies_count', 'desc')
                                                                ->orderBy('dependents_count', 'desc')
                                                                ->limit(10)
                                                                ->get(['id', 'name', 'display_name', 'module'])
                                                                ->toArray();

                       // 檢查潛在的循環依賴
                       $stats['circular_dependencies'] = $this->detectPotentialCircularDependencies();

                       // 依賴深度分佈
                       $stats['dependency_depth_distribution'] = $this->calculateDependencyDepthDistribution();

                       return $stats;
                   });
    }

    /**
     * 檢測潛在的循環依賴
     * 
     * @return array
     */
    private function detectPotentialCircularDependencies(): array
    {
        // 這裡實作循環依賴檢測邏輯
        // 目前返回空陣列，實際實作需要更複雜的圖算法
        return [];
    }

    /**
     * 計算依賴深度分佈
     * 
     * @return array
     */
    private function calculateDependencyDepthDistribution(): array
    {
        $distribution = [
            'depth_0' => 0, // 無依賴
            'depth_1' => 0, // 直接依賴
            'depth_2' => 0, // 二級依賴
            'depth_3_plus' => 0, // 三級以上依賴
        ];

        // 這裡需要實作深度計算邏輯
        // 目前返回模擬數據
        $distribution['depth_0'] = Permission::doesntHave('dependencies')->count();
        $distribution['depth_1'] = Permission::has('dependencies')->count();

        return $distribution;
    }

    /**
     * 優化依賴關係查詢
     * 
     * @param array $permissionIds 權限 ID 陣列
     * @return array
     */
    public function optimizedBatchDependencyQuery(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }

        $cacheKey = 'optimized_batch_deps_' . md5(implode(',', sort($permissionIds)));
        
        return Cache::tags([self::CACHE_TAG_DEPENDENCIES])
                   ->remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($permissionIds) {
                       // 使用單一查詢取得所有依賴關係
                       $dependencies = DB::table('permission_dependencies as pd')
                                        ->join('permissions as p_from', 'pd.permission_id', '=', 'p_from.id')
                                        ->join('permissions as p_to', 'pd.depends_on_permission_id', '=', 'p_to.id')
                                        ->whereIn('pd.permission_id', $permissionIds)
                                        ->select([
                                            'pd.permission_id',
                                            'pd.depends_on_permission_id',
                                            'p_from.name as from_name',
                                            'p_from.display_name as from_display_name',
                                            'p_from.module as from_module',
                                            'p_to.name as to_name',
                                            'p_to.display_name as to_display_name',
                                            'p_to.module as to_module',
                                        ])
                                        ->get();

                       // 組織結果
                       $result = [];
                       foreach ($permissionIds as $permissionId) {
                           $result[$permissionId] = [
                               'dependencies' => [],
                               'dependents' => [],
                           ];
                       }

                       foreach ($dependencies as $dep) {
                           $result[$dep->permission_id]['dependencies'][] = [
                               'id' => $dep->depends_on_permission_id,
                               'name' => $dep->to_name,
                               'display_name' => $dep->to_display_name,
                               'module' => $dep->to_module,
                           ];
                       }

                       // 取得被依賴關係
                       $dependents = DB::table('permission_dependencies as pd')
                                      ->join('permissions as p_from', 'pd.permission_id', '=', 'p_from.id')
                                      ->join('permissions as p_to', 'pd.depends_on_permission_id', '=', 'p_to.id')
                                      ->whereIn('pd.depends_on_permission_id', $permissionIds)
                                      ->select([
                                          'pd.depends_on_permission_id',
                                          'pd.permission_id',
                                          'p_from.name as from_name',
                                          'p_from.display_name as from_display_name',
                                          'p_from.module as from_module',
                                      ])
                                      ->get();

                       foreach ($dependents as $dep) {
                           if (isset($result[$dep->depends_on_permission_id])) {
                               $result[$dep->depends_on_permission_id]['dependents'][] = [
                                   'id' => $dep->permission_id,
                                   'name' => $dep->from_name,
                                   'display_name' => $dep->from_display_name,
                                   'module' => $dep->from_module,
                               ];
                           }
                       }

                       return $result;
                   });
    }

    /**
     * 清除依賴關係快取
     * 
     * @param int|null $permissionId 特定權限 ID，null 表示清除所有
     */
    public function clearDependencyCache(?int $permissionId = null): void
    {
        if ($permissionId) {
            // 清除特定權限的快取
            Cache::forget("full_dependency_tree_{$permissionId}_forward_only");
            Cache::forget("full_dependency_tree_{$permissionId}_with_reverse");
            Cache::forget("direct_relations_{$permissionId}_forward");
            Cache::forget("direct_relations_{$permissionId}_reverse");
            Cache::forget("permission_basic_info_{$permissionId}");
        } else {
            // 清除所有依賴關係快取
            Cache::tags([
                self::CACHE_TAG_DEPENDENCIES,
                self::CACHE_TAG_DEPENDENCY_TREE,
                self::CACHE_TAG_CIRCULAR_CHECK
            ])->flush();
        }
    }

    /**
     * 預熱依賴關係快取
     * 
     * @param array $permissionIds 權限 ID 陣列
     */
    public function warmupDependencyCache(array $permissionIds): void
    {
        Log::info('開始預熱依賴關係快取', ['count' => count($permissionIds)]);

        // 批量預熱
        $this->optimizedBatchDependencyQuery($permissionIds);

        // 預熱複雜權限的完整依賴樹
        $complexPermissions = array_slice($permissionIds, 0, 20); // 限制數量避免過度載入
        foreach ($complexPermissions as $permissionId) {
            $this->getFullDependencyTree($permissionId, true);
        }

        Log::info('依賴關係快取預熱完成');
    }
}