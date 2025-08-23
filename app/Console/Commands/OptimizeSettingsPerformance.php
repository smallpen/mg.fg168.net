<?php

namespace App\Console\Commands;

use App\Models\SettingPerformanceMetric;
use App\Services\SettingsCacheService;
use App\Services\SettingsPerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 設定效能優化指令
 * 
 * 提供各種設定效能優化操作
 */
class OptimizeSettingsPerformance extends Command
{
    /**
     * 指令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'settings:optimize
                            {--action=all : 優化動作 (cache, metrics, indexes, all)}
                            {--warmup-categories=* : 要預熱的快取分類}
                            {--cleanup-days=30 : 清理多少天前的效能指標}
                            {--batch-size=100 : 批量處理大小}
                            {--force : 強制執行，不詢問確認}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '優化設定系統效能，包括快取預熱、指標清理、索引優化等';

    /**
     * 快取服務
     */
    protected SettingsCacheService $cacheService;

    /**
     * 效能服務
     */
    protected SettingsPerformanceService $performanceService;

    /**
     * 建構函式
     */
    public function __construct(
        SettingsCacheService $cacheService,
        SettingsPerformanceService $performanceService
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->performanceService = $performanceService;
    }

    /**
     * 執行指令
     *
     * @return int
     */
    public function handle(): int
    {
        $action = $this->option('action');
        $force = $this->option('force');

        $this->info('🚀 開始設定效能優化...');
        $this->newLine();

        try {
            switch ($action) {
                case 'cache':
                    return $this->optimizeCache($force);
                case 'metrics':
                    return $this->optimizeMetrics($force);
                case 'indexes':
                    return $this->optimizeIndexes($force);
                case 'all':
                default:
                    return $this->optimizeAll($force);
            }
        } catch (\Exception $e) {
            $this->error("優化過程中發生錯誤: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 執行所有優化
     *
     * @param bool $force
     * @return int
     */
    protected function optimizeAll(bool $force): int
    {
        $this->info('📊 執行完整效能優化...');
        
        $results = [
            'cache' => $this->optimizeCache($force, false),
            'metrics' => $this->optimizeMetrics($force, false),
            'indexes' => $this->optimizeIndexes($force, false),
        ];

        $this->newLine();
        $this->info('✅ 完整優化完成！');
        
        // 顯示總結
        $this->displayOptimizationSummary($results);
        
        return array_sum($results) === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * 優化快取
     *
     * @param bool $force
     * @param bool $showSummary
     * @return int
     */
    protected function optimizeCache(bool $force, bool $showSummary = true): int
    {
        $this->info('🗄️  優化設定快取...');

        try {
            // 清理過期快取
            $result = $this->cacheService->flush('*expired*');
            if ($result) {
                $this->info('  ✓ 清理過期快取成功');
            } else {
                $this->warn('  ⚠ 清理過期快取失敗');
            }

            // 預熱快取
            $categories = $this->option('warmup-categories');
            if (empty($categories)) {
                $categories = []; // 空陣列表示所有分類
            }

            // 預熱設定快取
            $warmupResults = $this->performanceService->warmupCache($categories);
            if ($warmupResults['success']) {
                $this->info('  ✓ 預熱設定快取成功');
            } else {
                $this->warn('  ⚠ 預熱設定快取失敗');
            }

            if ($warmupResults) {
                $this->info("  ✓ 已預熱 {$warmupResults['cached_settings']} 個設定");
                $this->info("  ✓ 處理了 {$warmupResults['categories_processed']} 個分類");
                $this->info("  ✓ 執行時間: " . round($warmupResults['execution_time'], 2) . " ms");
            }

            // 顯示快取統計
            $stats = $this->cacheService->getStats();
            $this->newLine();
            $this->info('📈 快取統計:');
            $this->table(
                ['指標', '數值'],
                [
                    ['命中次數', number_format($stats['hits'])],
                    ['未命中次數', number_format($stats['misses'])],
                    ['命中率', $stats['hit_rate'] . '%'],
                    ['記憶體快取大小', number_format($stats['memory_cache_size'])],
                ]
            );

            if ($showSummary) {
                $this->info('✅ 快取優化完成！');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("快取優化失敗: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 優化效能指標
     *
     * @param bool $force
     * @param bool $showSummary
     * @return int
     */
    protected function optimizeMetrics(bool $force, bool $showSummary = true): int
    {
        $this->info('📊 優化效能指標...');

        try {
            $cleanupDays = (int) $this->option('cleanup-days');
            
            // 清理舊的效能指標
            $cleanedCount = $this->performanceService->cleanupPerformanceMetrics($cleanupDays);
            $this->info("  ✓ 清理舊的效能指標成功，清理了 {$cleanedCount} 條記錄");

            $this->info("  ✓ 已清理 {$cleanedCount} 條舊的效能指標記錄");

            // 顯示效能統計
            $stats = $this->performanceService->getPerformanceStats(24);
            if (!empty($stats)) {
                $this->newLine();
                $this->info('📈 過去 24 小時效能統計:');
                
                // 快取效能
                if (isset($stats['cache_performance'])) {
                    $cache = $stats['cache_performance'];
                    $this->info("  快取命中率: {$cache['hit_rate']}%");
                    $this->info("  平均響應時間: " . round($cache['avg_response_time'], 2) . " ms");
                    $this->info("  總請求數: " . number_format($cache['total_requests']));
                }

                // 批量操作效能
                if (isset($stats['batch_operations'])) {
                    $batch = $stats['batch_operations'];
                    $this->info("  平均批量處理時間: " . round($batch['avg_batch_time'], 2) . " ms");
                    $this->info("  最大批量處理時間: " . round($batch['max_batch_time'], 2) . " ms");
                    $this->info("  總批量操作數: " . number_format($batch['total_batches']));
                }

                // 錯誤統計
                if (isset($stats['errors'])) {
                    $errors = $stats['errors'];
                    $this->info("  錯誤總數: " . number_format($errors['total_errors']));
                    $this->info("  錯誤率: {$errors['error_rate']}%");
                }
            }

            // 分析慢查詢
            $this->analyzeSlowOperations();

            if ($showSummary) {
                $this->info('✅ 效能指標優化完成！');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("效能指標優化失敗: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 優化資料庫索引
     *
     * @param bool $force
     * @param bool $showSummary
     * @return int
     */
    protected function optimizeIndexes(bool $force, bool $showSummary = true): int
    {
        $this->info('🗃️  優化資料庫索引...');

        try {
            // 分析索引使用情況
            if ($this->analyzeIndexUsage()) {
                $this->info('  ✓ 分析索引使用情況成功');
            } else {
                $this->warn('  ⚠ 分析索引使用情況失敗');
            }

            // 優化表統計資訊
            if ($this->updateTableStatistics()) {
                $this->info('  ✓ 更新表統計資訊成功');
            } else {
                $this->warn('  ⚠ 更新表統計資訊失敗');
            }

            // 檢查缺失的索引
            if ($this->checkMissingIndexes()) {
                $this->info('  ✓ 檢查缺失的索引成功');
            } else {
                $this->warn('  ⚠ 檢查缺失的索引失敗');
            }

            if ($showSummary) {
                $this->info('✅ 索引優化完成！');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("索引優化失敗: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 分析慢操作
     *
     * @return void
     */
    protected function analyzeSlowOperations(): void
    {
        $slowOperations = SettingPerformanceMetric::where('value', '>', 1000) // 超過 1 秒
            ->where('recorded_at', '>=', now()->subHours(24))
            ->orderBy('value', 'desc')
            ->limit(10)
            ->get();

        if ($slowOperations->isNotEmpty()) {
            $this->newLine();
            $this->warn('⚠️  發現慢操作 (過去 24 小時):');
            
            $tableData = $slowOperations->map(function ($metric) {
                return [
                    $metric->operation,
                    round($metric->value, 2) . ' ' . $metric->unit,
                    $metric->recorded_at->format('H:i:s'),
                ];
            })->toArray();

            $this->table(['操作', '執行時間', '時間'], $tableData);
        }
    }

    /**
     * 分析索引使用情況
     *
     * @return bool
     */
    protected function analyzeIndexUsage(): bool
    {
        try {
            // 查詢索引使用統計（MySQL 特定）
            $indexStats = DB::select("
                SELECT 
                    TABLE_NAME,
                    INDEX_NAME,
                    CARDINALITY,
                    NULLABLE
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME IN ('settings', 'setting_changes', 'setting_backups', 'setting_performance_metrics')
                ORDER BY TABLE_NAME, CARDINALITY DESC
            ");

            if (!empty($indexStats)) {
                $this->info('  ✓ 分析了 ' . count($indexStats) . ' 個索引');
            }

            return true;
        } catch (\Exception $e) {
            $this->warn("  索引分析失敗: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * 更新表統計資訊
     *
     * @return bool
     */
    protected function updateTableStatistics(): bool
    {
        try {
            $tables = ['settings', 'setting_changes', 'setting_backups', 'setting_performance_metrics'];
            
            foreach ($tables as $table) {
                DB::statement("ANALYZE TABLE {$table}");
            }

            $this->info('  ✓ 更新了 ' . count($tables) . ' 個表的統計資訊');
            return true;
        } catch (\Exception $e) {
            $this->warn("  統計資訊更新失敗: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * 檢查缺失的索引
     *
     * @return bool
     */
    protected function checkMissingIndexes(): bool
    {
        try {
            // 檢查常用查詢的索引覆蓋情況
            $missingIndexes = [];

            // 檢查 settings 表的索引
            $settingsIndexes = DB::select("SHOW INDEX FROM settings");
            $indexNames = collect($settingsIndexes)->pluck('Key_name')->unique()->toArray();

            $recommendedIndexes = [
                'idx_settings_category_sort',
                'idx_settings_type_category',
                'idx_settings_system_public',
                'idx_settings_public_category_sort',
            ];

            foreach ($recommendedIndexes as $index) {
                if (!in_array($index, $indexNames)) {
                    $missingIndexes[] = "settings.{$index}";
                }
            }

            if (!empty($missingIndexes)) {
                $this->warn('  ⚠️  發現缺失的建議索引:');
                foreach ($missingIndexes as $index) {
                    $this->warn("    - {$index}");
                }
            } else {
                $this->info('  ✓ 所有建議的索引都已存在');
            }

            return true;
        } catch (\Exception $e) {
            $this->warn("  索引檢查失敗: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * 顯示優化總結
     *
     * @param array $results
     * @return void
     */
    protected function displayOptimizationSummary(array $results): void
    {
        $this->newLine();
        $this->info('📋 優化總結:');
        
        $tableData = [];
        foreach ($results as $operation => $result) {
            $status = $result === Command::SUCCESS ? '✅ 成功' : '❌ 失敗';
            $tableData[] = [ucfirst($operation), $status];
        }

        $this->table(['優化項目', '狀態'], $tableData);

        // 顯示建議
        $this->newLine();
        $this->info('💡 建議:');
        $this->info('  • 定期執行此指令以維持最佳效能');
        $this->info('  • 監控慢查詢並適時調整索引');
        $this->info('  • 根據使用模式調整快取策略');
        $this->info('  • 定期清理舊的效能指標資料');
    }
}