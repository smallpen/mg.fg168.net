<?php

namespace App\Console\Commands;

use App\Services\PermissionCacheService;
use App\Services\PermissionDependencyCacheService;
use App\Services\PermissionPerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 權限效能優化指令
 */
class OptimizePermissionPerformance extends Command
{
    /**
     * 指令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'permission:optimize 
                           {--cache : 預熱權限快取}
                           {--dependencies : 預熱依賴關係快取}
                           {--cleanup : 清理過期資料}
                           {--analyze : 分析效能並生成報告}
                           {--auto : 自動執行所有優化}
                           {--force : 強制執行，忽略警告}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '優化權限系統效能，包含快取預熱、資料清理和效能分析';

    protected PermissionCacheService $cacheService;
    protected PermissionDependencyCacheService $dependencyService;
    protected PermissionPerformanceService $performanceService;

    /**
     * 建構函式
     */
    public function __construct(
        PermissionCacheService $cacheService,
        PermissionDependencyCacheService $dependencyService,
        PermissionPerformanceService $performanceService
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->dependencyService = $dependencyService;
        $this->performanceService = $performanceService;
    }

    /**
     * 執行指令
     */
    public function handle(): int
    {
        $this->info('🚀 開始權限效能優化...');
        $startTime = microtime(true);

        try {
            if ($this->option('auto')) {
                $this->runAutoOptimization();
            } else {
                $this->runSelectedOptimizations();
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("✅ 權限效能優化完成！執行時間: {$executionTime}ms");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ 優化過程中發生錯誤: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 執行自動優化
     */
    private function runAutoOptimization(): void
    {
        $this->info('🔄 執行自動優化...');

        // 1. 清理過期資料
        $this->cleanupExpiredData();

        // 2. 預熱快取
        $this->warmupCaches();

        // 3. 優化資料庫
        $this->optimizeDatabase();

        // 4. 生成效能報告
        $this->generatePerformanceReport();

        // 5. 自動優化
        $this->runAutoPerformanceOptimization();
    }

    /**
     * 執行選定的優化
     */
    private function runSelectedOptimizations(): void
    {
        if ($this->option('cleanup')) {
            $this->cleanupExpiredData();
        }

        if ($this->option('cache')) {
            $this->warmupPermissionCache();
        }

        if ($this->option('dependencies')) {
            $this->warmupDependencyCache();
        }

        if ($this->option('analyze')) {
            $this->generatePerformanceReport();
        }

        if (!$this->hasAnyOption()) {
            $this->showUsageHelp();
        }
    }

    /**
     * 清理過期資料
     */
    private function cleanupExpiredData(): void
    {
        $this->info('🧹 清理過期資料...');

        $bar = $this->output->createProgressBar(4);
        $bar->start();

        // 清理過期快取
        $cacheCleanup = $this->cacheService->cleanupExpiredCache();
        $this->line("  - 清理過期快取: {$cacheCleanup} 項目");
        $bar->advance();

        // 清理舊的效能資料
        $metricsCleanup = $this->performanceService->cleanupOldMetrics(30);
        $this->line("  - 清理效能資料: {$metricsCleanup} 筆記錄");
        $bar->advance();

        // 清理快取統計資料
        $statsCleanup = DB::table('permission_cache_statistics')
                         ->where('last_accessed_at', '<', now()->subDays(30))
                         ->delete();
        $this->line("  - 清理快取統計: {$statsCleanup} 筆記錄");
        $bar->advance();

        // 清理依賴關係快取
        $this->dependencyService->clearDependencyCache();
        $this->line("  - 清理依賴關係快取: 完成");
        $bar->advance();

        $bar->finish();
        $this->newLine();
    }

    /**
     * 預熱權限快取
     */
    private function warmupPermissionCache(): void
    {
        $this->info('🔥 預熱權限快取...');

        $bar = $this->output->createProgressBar(3);
        $bar->start();

        // 智慧預熱
        $this->cacheService->intelligentWarmup();
        $bar->advance();

        // 預熱統計資料
        $this->cacheService->getPermissionStatsSummary();
        $bar->advance();

        // 預熱權限樹
        $this->cacheService->getPermissionTree();
        $bar->advance();

        $bar->finish();
        $this->newLine();
        $this->info('  ✅ 權限快取預熱完成');
    }

    /**
     * 預熱依賴關係快取
     */
    private function warmupDependencyCache(): void
    {
        $this->info('🔗 預熱依賴關係快取...');

        // 取得有依賴關係的權限
        $permissionsWithDeps = DB::table('permission_dependencies')
                                ->distinct()
                                ->pluck('permission_id')
                                ->toArray();

        if (empty($permissionsWithDeps)) {
            $this->info('  ℹ️  沒有找到依賴關係，跳過預熱');
            return;
        }

        $bar = $this->output->createProgressBar(count($permissionsWithDeps));
        $bar->start();

        $chunks = array_chunk($permissionsWithDeps, 20);
        foreach ($chunks as $chunk) {
            $this->dependencyService->warmupDependencyCache($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info('  ✅ 依賴關係快取預熱完成');
    }

    /**
     * 預熱所有快取
     */
    private function warmupCaches(): void
    {
        $this->info('🔥 預熱所有快取...');
        $this->warmupPermissionCache();
        $this->warmupDependencyCache();
    }

    /**
     * 優化資料庫
     */
    private function optimizeDatabase(): void
    {
        $this->info('🗄️  優化資料庫...');

        if (!$this->option('force') && !$this->confirm('是否要執行資料庫優化？這可能需要一些時間。')) {
            $this->info('  ⏭️  跳過資料庫優化');
            return;
        }

        $bar = $this->output->createProgressBar(4);
        $bar->start();

        try {
            // 分析資料表
            DB::statement('ANALYZE TABLE permissions');
            $bar->advance();

            DB::statement('ANALYZE TABLE permission_dependencies');
            $bar->advance();

            DB::statement('ANALYZE TABLE role_permissions');
            $bar->advance();

            // 優化資料表
            DB::statement('OPTIMIZE TABLE permissions');
            $bar->advance();

            $bar->finish();
            $this->newLine();
            $this->info('  ✅ 資料庫優化完成');
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->warn("  ⚠️  資料庫優化失敗: {$e->getMessage()}");
        }
    }

    /**
     * 生成效能報告
     */
    private function generatePerformanceReport(): void
    {
        $this->info('📊 生成效能報告...');

        $report = $this->performanceService->getPerformanceReport(7);
        $cacheStats = $this->performanceService->getCachePerformanceStats();

        $this->displayPerformanceReport($report, $cacheStats);
    }

    /**
     * 顯示效能報告
     */
    private function displayPerformanceReport(array $report, array $cacheStats): void
    {
        $this->newLine();
        $this->info('📈 效能報告 (最近 7 天)');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // 基本統計
        $basic = $report['basic_stats'];
        $this->table(['指標', '數值'], [
            ['總操作數', number_format($basic->total_operations)],
            ['平均執行時間', round($basic->avg_execution_time, 2) . ' ms'],
            ['最大執行時間', round($basic->max_execution_time, 2) . ' ms'],
            ['平均記憶體使用', $this->formatBytes($basic->avg_memory_usage ?? 0)],
            ['最大記憶體使用', $this->formatBytes($basic->max_memory_usage ?? 0)],
        ]);

        // 快取統計
        if (!empty($cacheStats['overall_stats'])) {
            $cache = $cacheStats['overall_stats'];
            $this->newLine();
            $this->info('💾 快取統計');
            $this->table(['指標', '數值'], [
                ['快取鍵總數', number_format($cache->total_cache_keys)],
                ['總命中次數', number_format($cache->total_hits)],
                ['總未命中次數', number_format($cache->total_misses)],
                ['平均命中率', round($cache->avg_hit_rate * 100, 2) . '%'],
                ['快取總大小', $this->formatBytes($cache->total_cache_size)],
            ]);
        }

        // 建議
        if (!empty($report['recommendations'])) {
            $this->newLine();
            $this->info('💡 優化建議');
            foreach ($report['recommendations'] as $rec) {
                $priority = $rec['priority'] === 'high' ? '🔴' : ($rec['priority'] === 'medium' ? '🟡' : '🟢');
                $this->line("  {$priority} {$rec['message']}");
            }
        }

        if (!empty($cacheStats['recommendations'])) {
            foreach ($cacheStats['recommendations'] as $rec) {
                $priority = $rec['priority'] === 'high' ? '🔴' : ($rec['priority'] === 'medium' ? '🟡' : '🟢');
                $this->line("  {$priority} {$rec['message']}");
            }
        }
    }

    /**
     * 執行自動效能優化
     */
    private function runAutoPerformanceOptimization(): void
    {
        $this->info('⚡ 執行自動效能優化...');

        $optimizations = $this->performanceService->autoOptimize();
        
        if (empty($optimizations)) {
            $this->info('  ℹ️  沒有需要優化的項目');
        } else {
            foreach ($optimizations as $optimization) {
                $this->line("  ✅ {$optimization}");
            }
        }
    }

    /**
     * 檢查是否有任何選項被設定
     */
    private function hasAnyOption(): bool
    {
        return $this->option('cache') || 
               $this->option('dependencies') || 
               $this->option('cleanup') || 
               $this->option('analyze') || 
               $this->option('auto');
    }

    /**
     * 顯示使用說明
     */
    private function showUsageHelp(): void
    {
        $this->info('🔧 權限效能優化工具');
        $this->newLine();
        $this->line('可用選項:');
        $this->line('  --cache        預熱權限快取');
        $this->line('  --dependencies 預熱依賴關係快取');
        $this->line('  --cleanup      清理過期資料');
        $this->line('  --analyze      分析效能並生成報告');
        $this->line('  --auto         自動執行所有優化');
        $this->line('  --force        強制執行，忽略警告');
        $this->newLine();
        $this->line('範例:');
        $this->line('  php artisan permission:optimize --auto');
        $this->line('  php artisan permission:optimize --cache --dependencies');
        $this->line('  php artisan permission:optimize --analyze');
    }

    /**
     * 格式化位元組數
     */
    private function formatBytes(?int $bytes): string
    {
        if ($bytes === null || $bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
    }
}