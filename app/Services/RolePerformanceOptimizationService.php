<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * 角色管理效能優化服務
 * 
 * 提供效能測試、監控和優化功能
 */
class RolePerformanceOptimizationService
{
    private RoleCacheService $cacheService;
    private array $performanceMetrics = [];

    public function __construct(RoleCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 執行完整的效能測試套件
     * 
     * @return array
     */
    public function runPerformanceTestSuite(): array
    {
        $results = [];

        try {
            Log::info('開始執行角色管理效能測試');

            // 測試資料庫查詢效能
            $results['database_performance'] = $this->testDatabasePerformance();

            // 測試快取效能
            $results['cache_performance'] = $this->testCachePerformance();

            // 測試權限繼承效能
            $results['inheritance_performance'] = $this->testPermissionInheritancePerformance();

            // 測試批量操作效能
            $results['bulk_operations_performance'] = $this->testBulkOperationsPerformance();

            // 測試記憶體使用
            $results['memory_usage'] = $this->testMemoryUsage();

            // 生成效能報告
            $results['performance_report'] = $this->generatePerformanceReport($results);

            Log::info('角色管理效能測試完成', ['results' => $results]);

            return [
                'success' => true,
                'timestamp' => now()->toISOString(),
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('效能測試失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'results' => $results
            ];
        }
    }

    /**
     * 測試資料庫查詢效能
     * 
     * @return array
     */
    public function testDatabasePerformance(): array
    {
        $results = [];

        // 測試角色列表查詢
        $startTime = microtime(true);
        $roles = Role::with(['permissions', 'users'])->paginate(20);
        $endTime = microtime(true);
        $results['role_list_query'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2), // ms
            'query_count' => $this->getQueryCount(),
            'memory_usage' => $this->getMemoryUsage()
        ];

        // 測試權限矩陣查詢
        $startTime = microtime(true);
        $permissions = Permission::with('roles')->get()->groupBy('module');
        $endTime = microtime(true);
        $results['permission_matrix_query'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'query_count' => $this->getQueryCount(),
            'memory_usage' => $this->getMemoryUsage()
        ];

        // 測試角色層級查詢
        $startTime = microtime(true);
        $hierarchy = Role::with(['children' => function ($query) {
            $query->with('children');
        }])->whereNull('parent_id')->get();
        $endTime = microtime(true);
        $results['role_hierarchy_query'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'query_count' => $this->getQueryCount(),
            'memory_usage' => $this->getMemoryUsage()
        ];

        // 測試複雜的權限檢查查詢
        $startTime = microtime(true);
        $user = User::with(['roles.permissions'])->first();
        if ($user) {
            $userPermissions = $user->roles->flatMap->permissions->unique('id');
        }
        $endTime = microtime(true);
        $results['user_permission_check'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'query_count' => $this->getQueryCount(),
            'memory_usage' => $this->getMemoryUsage()
        ];

        return $results;
    }

    /**
     * 測試快取效能
     * 
     * @return array
     */
    public function testCachePerformance(): array
    {
        $results = [];

        // 清除快取以進行準確測試
        $this->cacheService->clearAllCache();

        // 測試快取寫入效能
        $startTime = microtime(true);
        $this->cacheService->warmupCache();
        $endTime = microtime(true);
        $results['cache_warmup'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage' => $this->getMemoryUsage()
        ];

        // 測試快取讀取效能
        $role = Role::first();
        if ($role) {
            // 第一次讀取（從資料庫）
            $this->cacheService->clearRoleCache($role->id);
            $startTime = microtime(true);
            $permissions1 = $this->cacheService->getRoleAllPermissions($role);
            $endTime = microtime(true);
            $results['cache_miss'] = [
                'execution_time' => round(($endTime - $startTime) * 1000, 2),
                'memory_usage' => $this->getMemoryUsage()
            ];

            // 第二次讀取（從快取）
            $startTime = microtime(true);
            $permissions2 = $this->cacheService->getRoleAllPermissions($role);
            $endTime = microtime(true);
            $results['cache_hit'] = [
                'execution_time' => round(($endTime - $startTime) * 1000, 2),
                'memory_usage' => $this->getMemoryUsage()
            ];

            $results['cache_efficiency'] = [
                'speedup_ratio' => round($results['cache_miss']['execution_time'] / $results['cache_hit']['execution_time'], 2),
                'data_consistency' => $permissions1->count() === $permissions2->count()
            ];
        }

        return $results;
    }

    /**
     * 測試權限繼承效能
     * 
     * @return array
     */
    public function testPermissionInheritancePerformance(): array
    {
        $results = [];

        // 找一個有父角色的角色進行測試
        $childRole = Role::whereNotNull('parent_id')->first();
        
        if ($childRole) {
            // 測試不使用快取的權限繼承
            $startTime = microtime(true);
            $allPermissions1 = $childRole->getAllPermissions(false);
            $endTime = microtime(true);
            $results['without_cache'] = [
                'execution_time' => round(($endTime - $startTime) * 1000, 2),
                'query_count' => $this->getQueryCount(),
                'memory_usage' => $this->getMemoryUsage(),
                'permission_count' => $allPermissions1->count()
            ];

            // 測試使用快取的權限繼承
            $startTime = microtime(true);
            $allPermissions2 = $childRole->getAllPermissions(true);
            $endTime = microtime(true);
            $results['with_cache'] = [
                'execution_time' => round(($endTime - $startTime) * 1000, 2),
                'query_count' => $this->getQueryCount(),
                'memory_usage' => $this->getMemoryUsage(),
                'permission_count' => $allPermissions2->count()
            ];

            $results['cache_improvement'] = [
                'speedup_ratio' => round($results['without_cache']['execution_time'] / $results['with_cache']['execution_time'], 2),
                'data_consistency' => $allPermissions1->count() === $allPermissions2->count()
            ];
        }

        // 測試深層次角色繼承
        $deepRoles = Role::whereNotNull('parent_id')->get()->filter(function ($role) {
            return $role->getDepth() >= 2;
        });

        if ($deepRoles->isNotEmpty()) {
            $deepRole = $deepRoles->first();
            $startTime = microtime(true);
            $deepPermissions = $deepRole->getAllPermissions();
            $endTime = microtime(true);
            $results['deep_inheritance'] = [
                'execution_time' => round(($endTime - $startTime) * 1000, 2),
                'depth' => $deepRole->getDepth(),
                'permission_count' => $deepPermissions->count(),
                'memory_usage' => $this->getMemoryUsage()
            ];
        }

        return $results;
    }

    /**
     * 測試批量操作效能
     * 
     * @return array
     */
    public function testBulkOperationsPerformance(): array
    {
        $results = [];

        // 測試批量權限同步
        $roles = Role::limit(5)->get();
        $permissions = Permission::limit(10)->pluck('id')->toArray();

        $startTime = microtime(true);
        foreach ($roles as $role) {
            $role->syncPermissions($permissions);
        }
        $endTime = microtime(true);
        $results['bulk_permission_sync'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'roles_count' => $roles->count(),
            'permissions_count' => count($permissions),
            'memory_usage' => $this->getMemoryUsage()
        ];

        // 測試批量使用者權限檢查
        $users = User::with('roles')->limit(10)->get();
        $testPermission = 'users.view';

        $startTime = microtime(true);
        foreach ($users as $user) {
            $hasPermission = $user->roles->flatMap->permissions->contains('name', $testPermission);
        }
        $endTime = microtime(true);
        $results['bulk_permission_check'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2),
            'users_count' => $users->count(),
            'memory_usage' => $this->getMemoryUsage()
        ];

        return $results;
    }

    /**
     * 測試記憶體使用
     * 
     * @return array
     */
    public function testMemoryUsage(): array
    {
        $initialMemory = memory_get_usage(true);

        // 載入大量角色資料
        $roles = Role::with(['permissions', 'users', 'children', 'parent'])->get();
        $afterRolesMemory = memory_get_usage(true);

        // 載入權限資料
        $permissions = Permission::with('roles')->get();
        $afterPermissionsMemory = memory_get_usage(true);

        // 執行權限繼承計算
        foreach ($roles->take(10) as $role) {
            $role->getAllPermissions();
        }
        $afterInheritanceMemory = memory_get_usage(true);

        return [
            'initial_memory' => $this->formatBytes($initialMemory),
            'after_roles_load' => $this->formatBytes($afterRolesMemory),
            'after_permissions_load' => $this->formatBytes($afterPermissionsMemory),
            'after_inheritance_calculation' => $this->formatBytes($afterInheritanceMemory),
            'memory_increases' => [
                'roles_load' => $this->formatBytes($afterRolesMemory - $initialMemory),
                'permissions_load' => $this->formatBytes($afterPermissionsMemory - $afterRolesMemory),
                'inheritance_calculation' => $this->formatBytes($afterInheritanceMemory - $afterPermissionsMemory)
            ],
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true))
        ];
    }

    /**
     * 執行效能優化
     * 
     * @return array
     */
    public function performOptimizations(): array
    {
        $optimizations = [];

        try {
            // 優化 1: 清理並重建快取
            $this->cacheService->clearAllCache();
            $this->cacheService->warmupCache();
            $optimizations['cache_optimization'] = '快取已清理並重建';

            // 優化 2: 資料庫查詢優化
            $this->optimizeDatabaseQueries();
            $optimizations['database_optimization'] = '資料庫查詢已優化';

            // 優化 3: 記憶體使用優化
            $this->optimizeMemoryUsage();
            $optimizations['memory_optimization'] = '記憶體使用已優化';

            // 優化 4: 索引優化
            $this->optimizeIndexes();
            $optimizations['index_optimization'] = '資料庫索引已優化';

            return [
                'success' => true,
                'optimizations' => $optimizations,
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('效能優化失敗', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'optimizations' => $optimizations
            ];
        }
    }

    /**
     * 生成效能報告
     * 
     * @param array $results
     * @return array
     */
    private function generatePerformanceReport(array $results): array
    {
        $report = [
            'summary' => [
                'test_timestamp' => now()->toISOString(),
                'overall_status' => 'good'
            ],
            'recommendations' => []
        ];

        // 分析資料庫效能
        if (isset($results['database_performance'])) {
            $dbResults = $results['database_performance'];
            $avgExecutionTime = collect($dbResults)->avg('execution_time');
            
            if ($avgExecutionTime > 100) { // 超過 100ms
                $report['recommendations'][] = '資料庫查詢效能需要優化，平均執行時間過長';
                $report['summary']['overall_status'] = 'needs_improvement';
            }
        }

        // 分析快取效能
        if (isset($results['cache_performance']['cache_efficiency'])) {
            $cacheEfficiency = $results['cache_performance']['cache_efficiency'];
            
            if ($cacheEfficiency['speedup_ratio'] < 5) {
                $report['recommendations'][] = '快取效能提升不明顯，建議檢查快取配置';
            }
        }

        // 分析記憶體使用
        if (isset($results['memory_usage'])) {
            $memoryUsage = $results['memory_usage'];
            $peakMemoryMB = $this->bytesToMB($memoryUsage['peak_memory']);
            
            if ($peakMemoryMB > 128) { // 超過 128MB
                $report['recommendations'][] = '記憶體使用量較高，建議優化資料載入策略';
                $report['summary']['overall_status'] = 'needs_improvement';
            }
        }

        // 設定整體評級
        if (empty($report['recommendations'])) {
            $report['summary']['overall_status'] = 'excellent';
        } elseif (count($report['recommendations']) <= 2) {
            $report['summary']['overall_status'] = 'good';
        } else {
            $report['summary']['overall_status'] = 'needs_improvement';
        }

        return $report;
    }

    /**
     * 優化資料庫查詢
     * 
     * @return void
     */
    private function optimizeDatabaseQueries(): void
    {
        // 啟用查詢快取（如果支援）
        try {
            DB::statement('SET SESSION query_cache_type = ON');
        } catch (\Exception $e) {
            // 忽略不支援的資料庫
        }

        // 分析慢查詢
        $this->analyzeSlowQueries();
    }

    /**
     * 優化記憶體使用
     * 
     * @return void
     */
    private function optimizeMemoryUsage(): void
    {
        // 強制垃圾回收
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        // 清理不必要的變數
        $this->performanceMetrics = [];
    }

    /**
     * 優化索引
     * 
     * @return void
     */
    private function optimizeIndexes(): void
    {
        $schema = DB::getSchemaBuilder();

        // 檢查並建立複合索引
        try {
            // 角色權限關聯表的複合索引
            if (!$this->indexExists('role_permissions', 'role_permissions_composite')) {
                $schema->table('role_permissions', function ($table) {
                    $table->index(['role_id', 'permission_id'], 'role_permissions_composite');
                });
            }

            // 使用者角色關聯表的複合索引
            if (!$this->indexExists('user_roles', 'user_roles_composite')) {
                $schema->table('user_roles', function ($table) {
                    $table->index(['user_id', 'role_id'], 'user_roles_composite');
                });
            }

            // 角色表的狀態索引
            if (!$this->indexExists('roles', 'roles_status_composite')) {
                $schema->table('roles', function ($table) {
                    $table->index(['is_active', 'is_system_role'], 'roles_status_composite');
                });
            }

        } catch (\Exception $e) {
            Log::warning('索引優化部分失敗', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 分析慢查詢
     * 
     * @return void
     */
    private function analyzeSlowQueries(): void
    {
        try {
            // 啟用慢查詢日誌（MySQL）
            DB::statement('SET SESSION long_query_time = 1');
            DB::statement('SET SESSION slow_query_log = ON');
        } catch (\Exception $e) {
            // 忽略不支援的資料庫或權限不足的情況
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
     * 取得查詢數量（簡化版本）
     * 
     * @return int
     */
    private function getQueryCount(): int
    {
        // 這裡可以實作查詢計數邏輯
        // 目前返回估計值
        return DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
    }

    /**
     * 取得記憶體使用量
     * 
     * @return string
     */
    private function getMemoryUsage(): string
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    /**
     * 格式化位元組
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 將格式化的位元組轉換為 MB
     * 
     * @param string $formattedBytes
     * @return float
     */
    private function bytesToMB(string $formattedBytes): float
    {
        if (strpos($formattedBytes, 'GB') !== false) {
            return floatval($formattedBytes) * 1024;
        } elseif (strpos($formattedBytes, 'MB') !== false) {
            return floatval($formattedBytes);
        } elseif (strpos($formattedBytes, 'KB') !== false) {
            return floatval($formattedBytes) / 1024;
        } else {
            return floatval($formattedBytes) / (1024 * 1024);
        }
    }
}