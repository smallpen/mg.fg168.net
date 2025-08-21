<?php

namespace App\Console\Commands;

use App\Services\RoleCacheService;
use App\Services\RoleOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 角色快取管理命令
 * 
 * 提供角色快取的預熱、清除、統計等功能
 */
class RoleCacheCommand extends Command
{
    /**
     * 命令名稱和參數
     *
     * @var string
     */
    protected $signature = 'role:cache 
                            {action : 操作類型 (warmup|clear|stats|cleanup)}
                            {--role= : 特定角色 ID}
                            {--user= : 特定使用者 ID}
                            {--force : 強制執行}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '管理角色快取系統 (預熱、清除、統計、清理)';

    private RoleCacheService $cacheService;
    private RoleOptimizationService $optimizationService;

    /**
     * 建立命令實例
     */
    public function __construct(RoleCacheService $cacheService, RoleOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->optimizationService = $optimizationService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            switch ($action) {
                case 'warmup':
                    return $this->warmupCache();
                    
                case 'clear':
                    return $this->clearCache();
                    
                case 'stats':
                    return $this->showStats();
                    
                case 'cleanup':
                    return $this->cleanupCache();
                    
                default:
                    $this->error("未知的操作: {$action}");
                    $this->info('可用操作: warmup, clear, stats, cleanup');
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("執行命令時發生錯誤: {$e->getMessage()}");
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * 預熱快取
     */
    private function warmupCache(): int
    {
        $this->info('開始預熱角色快取...');
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // 執行快取預熱
        $this->cacheService->warmupCache();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = round(($endTime - $startTime) * 1000, 2);
        $memoryUsed = $this->formatBytes($endMemory - $startMemory);

        $this->info("快取預熱完成！");
        $this->info("執行時間: {$duration}ms");
        $this->info("記憶體使用: {$memoryUsed}");

        return 0;
    }

    /**
     * 清除快取
     */
    private function clearCache(): int
    {
        $roleId = $this->option('role');
        $userId = $this->option('user');
        $force = $this->option('force');

        if ($roleId) {
            if (!$force && !$this->confirm("確定要清除角色 ID {$roleId} 的快取嗎？")) {
                $this->info('操作已取消');
                return 0;
            }
            
            $this->info("清除角色 ID {$roleId} 的快取...");
            $this->cacheService->clearRoleCache((int) $roleId);
            $this->info('角色快取已清除');
            
        } elseif ($userId) {
            if (!$force && !$this->confirm("確定要清除使用者 ID {$userId} 的權限快取嗎？")) {
                $this->info('操作已取消');
                return 0;
            }
            
            $this->info("清除使用者 ID {$userId} 的權限快取...");
            $this->cacheService->clearUserPermissionCache((int) $userId);
            $this->info('使用者權限快取已清除');
            
        } else {
            if (!$force && !$this->confirm('確定要清除所有角色相關快取嗎？這可能會暫時影響系統效能。')) {
                $this->info('操作已取消');
                return 0;
            }
            
            $this->info('清除所有角色相關快取...');
            $this->cacheService->clearAllCache();
            $this->info('所有角色快取已清除');
        }

        return 0;
    }

    /**
     * 顯示快取統計
     */
    private function showStats(): int
    {
        $this->info('角色快取統計資訊');
        $this->info('==================');

        // 快取配置資訊
        $cacheStats = $this->cacheService->getCacheStats();
        
        $this->table(
            ['設定項目', '值'],
            [
                ['快取驅動', $cacheStats['cache_driver']],
                ['快取前綴', $cacheStats['cache_prefix']],
                ['預設 TTL', $cacheStats['ttl_settings']['default'] . ' 秒'],
                ['權限繼承 TTL', $cacheStats['ttl_settings']['permission_inheritance'] . ' 秒'],
                ['角色統計 TTL', $cacheStats['ttl_settings']['role_stats'] . ' 秒'],
            ]
        );

        // 記憶體使用統計
        $memoryStats = $this->optimizationService->getMemoryStats();
        
        $this->info("\n記憶體使用統計");
        $this->info('================');
        $this->table(
            ['項目', '值'],
            [
                ['目前記憶體使用', $this->formatBytes($memoryStats['memory_usage'])],
                ['記憶體使用峰值', $this->formatBytes($memoryStats['memory_peak'])],
                ['記憶體限制', $memoryStats['memory_limit']],
            ]
        );

        // 角色統計
        $roleStats = $this->cacheService->getRoleStats();
        
        $this->info("\n角色統計資訊");
        $this->info('============');
        $this->table(
            ['項目', '數量'],
            [
                ['總角色數', $roleStats['total_roles']],
                ['啟用角色數', $roleStats['active_roles']],
                ['系統角色數', $roleStats['system_roles']],
                ['有使用者的角色', $roleStats['roles_with_users']],
                ['有權限的角色', $roleStats['roles_with_permissions']],
                ['根角色數', $roleStats['root_roles']],
                ['子角色數', $roleStats['child_roles']],
                ['平均權限數/角色', round($roleStats['average_permissions_per_role'], 2)],
            ]
        );

        // 最常用角色
        if (!empty($roleStats['most_used_roles'])) {
            $this->info("\n最常用角色 (前5名)");
            $this->info('==================');
            $this->table(
                ['角色名稱', '使用者數量'],
                collect($roleStats['most_used_roles'])->map(function ($role) {
                    return [$role['name'], $role['users_count']];
                })->toArray()
            );
        }

        return 0;
    }

    /**
     * 清理快取
     */
    private function cleanupCache(): int
    {
        $force = $this->option('force');
        
        if (!$force && !$this->confirm('確定要執行快取清理嗎？這將清理孤立的關聯資料。')) {
            $this->info('操作已取消');
            return 0;
        }

        $this->info('開始清理孤立的關聯資料...');
        
        $results = $this->optimizationService->cleanupUnusedRelations();
        
        $this->info('清理完成！');
        $this->table(
            ['項目', '清理數量'],
            [
                ['孤立的角色權限關聯', $results['orphaned_role_permissions']],
                ['孤立的使用者角色關聯', $results['orphaned_user_roles']],
                ['孤立的權限依賴關聯', $results['orphaned_permission_dependencies']],
            ]
        );

        // 清理完成後重新預熱快取
        if ($this->confirm('是否要重新預熱快取？')) {
            return $this->warmupCache();
        }

        return 0;
    }

    /**
     * 格式化位元組大小
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
